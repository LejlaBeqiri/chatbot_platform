<?php

namespace App\Services;

use App\Jobs\SaveChatInteraction;
use App\Models\Chat;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Embedding;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use OpenAI;
use Pgvector\Laravel\Vector;
use Throwable;

class ChatService
{
    public function askQuestion(
        string $userQuestion,
        Chatbot $chatbot,
        ?int $tenantId = null,
        ?string $userIdentifier = null,
        bool $isTenantAccount = false
    ) {
        if (! $chatbot->is_active) {
            throw new \Exception('This chatbot is currently inactive.', 403);
        }

        $conversation = $this->getConversation($chatbot, $tenantId, $userIdentifier, $isTenantAccount);

        if (! $conversation) {
            throw new \Exception('Conversation session not found.', 404);
        }

        $recentChats = Chat::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $conversationHistory = $recentChats->sortBy('created_at');

        $systemPrompt = str_replace(
            '{todayDate}',
            Carbon::now()->toDateString(),
            $chatbot->chatbot_system_prompt
        );

        $embeddingInput = $conversationHistory->isNotEmpty()
            ? 'Previous question: '.$conversationHistory->last()->user_message
            .' Previous answer: '.$conversationHistory->last()->bot_response
            .' Current question: '.$userQuestion
            : $userQuestion;

        $searchResult = $this->performSemanticSearch($embeddingInput, $chatbot);

        $context    = null;
        $similarity = null;
        $threshold  = 0.5;

        if ($searchResult) {
            $context    = $searchResult['embedding']->source_text;
            $similarity = max(0.0, min(1.0, 1.0 - $searchResult['distance']));
        }


        $messages = [];

        // Parse the chatbot_system_prompt JSON if possible
        $systemPromptContent = '';
        $systemPromptJson = json_decode($chatbot->chatbot_system_prompt, true);
        if (is_array($systemPromptJson)) {
            if (isset($systemPromptJson['context'])) {
                $systemPromptContent .= $systemPromptJson['context'] . "\n";
            }
            if (isset($systemPromptJson['rules']) && is_array($systemPromptJson['rules'])) {
                $systemPromptContent .= "Rules:";
                foreach ($systemPromptJson['rules'] as $rule) {
                    $systemPromptContent .= "\n- " . $rule;
                }
                $systemPromptContent .= "\n";
            }
        } else {
            $systemPromptContent = $chatbot->chatbot_system_prompt . "\n";
        }

        $basePrompt = trim($systemPromptContent . "\nYou will be given an optional airline-policy context and a user question.\nA context-relevance score (similarity) between 0.0 and 1.0 is provided…\n");
        if ($context !== null) {
            $basePrompt .= "\n\nContext-similarity: {$similarity}\nPolicy-text: {$context}";
        }
        $messages[] = ['role' => 'system', 'content' => $basePrompt];

        foreach ($conversationHistory as $chat) {
            $messages[] = ['role' => 'user',      'content' => $chat->user_message];
            $messages[] = ['role' => 'assistant', 'content' => $chat->bot_response];
        }

        // push the newest question from user
        $messages[] = ['role' => 'user', 'content' => $userQuestion];

        return [
            'conversation' => $conversation,
            'messages'     => $messages,
            'chatbot'      => $chatbot,
            'userQuestion' => $userQuestion,
            'embeddingId'  => $searchResult['embedding']->id ?? null,
            'similarity'   => $similarity,
        ];
    }

    private function getConversation(
        Chatbot $chatbot,
        ?int $tenantId,
        ?string $userIdentifier,
        bool $isTenantAccount = false
    ): ?Conversation {
        if ($isTenantAccount) {
            return Conversation::firstOrCreate(
                [
                    'chatbot_id'      => $chatbot->id,
                    'tenant_id'       => $tenantId,
                    'user_identifier' => null,
                ],
                []
            );
        }

        if ($userIdentifier) {
            return Conversation::firstOrCreate(
                [
                    'chatbot_id'      => $chatbot->id,
                    'tenant_id'       => $tenantId,
                    'user_identifier' => $userIdentifier,
                ],
                []
            );
        }

        return null;
    }

    private function performSemanticSearch(string $embeddingInput, Chatbot $chatbot): ?array
    {
        $apiKey = $chatbot->tenant
            ->apiKeys()
            ->where('provider', 'OPENAI')
            ->firstOrFail()->key;

        if (! $apiKey) {
            Log::error('API key not found for tenant ID: '.$chatbot->tenant_id);

            return null;
        }
        $openAIDecryptedApiKey = Crypt::decryptString($apiKey);

        $embeddingResponse = OpenAI::client($openAIDecryptedApiKey)->embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $embeddingInput,
        ]);

        $embeddingResponseArray = $embeddingResponse->toArray();

        $questionEmbedding = $embeddingResponseArray['data'][0]['embedding'];
        $result            = Embedding::selectRaw('*, embedding <-> ? AS distance', [new Vector($questionEmbedding)])
            ->where('knowledge_base_id', $chatbot->knowledge_base_id)
            ->orderBy('distance', 'asc')
            ->first();

        if (! $result) {
            return null;
        }

        return [
            'embedding' => $result,
            'distance'  => (float) $result->distance,
        ];
    }

    public function handleChatStream(array $chatData, string $openAIDecryptedApiKey)
    {
        $streamParams = [
            'model'       => $chatData['chatbot']->model ?? 'gpt-4o-mini',
            'messages'    => $chatData['messages'],
            'temperature' => $chatData['chatbot']->temperature ?? 0.3,
        ];

        Log::info($streamParams);
        return response()->stream(function () use ($openAIDecryptedApiKey, $streamParams, $chatData) {
            $fullResponseContent = '';
            try {
                $stream = OpenAI::client($openAIDecryptedApiKey)
                    ->chat()
                    ->createStreamed($streamParams);

                foreach ($stream as $chunk) {
                    $deltaContent = $chunk->choices[0]->delta->content;
                    if (! empty($deltaContent)) {
                        $fullResponseContent .= $deltaContent;
                        echo 'data: '.json_encode(['text' => $deltaContent])."\n\n";
                        ob_flush();
                        flush();
                    }
                    if (property_exists($chunk->choices[0], 'finish_reason')) {
                        break;
                    }
                }
                echo "event: done\ndata: ".json_encode(['status' => 'completed'])."\n\n";
                ob_flush();
                flush();
            } catch (Throwable $e) {
                Log::error('OpenAI Stream Error: '.$e->getMessage(), ['exception' => $e]);
                echo "event: error\ndata: ".json_encode(['message' => 'An error occurred during streaming.'])."\n\n";
                ob_flush();
                flush();
            } finally {
                if (! empty(trim($fullResponseContent))) {
                    SaveChatInteraction::dispatch(
                        $chatData['conversation']->id,
                        $chatData['chatbot']->id,
                        $chatData['chatbot']->tenant_id,
                        $chatData['userQuestion'],
                        $fullResponseContent,
                        $chatData['embeddingId']
                    );
                } else {
                    Log::warning("No response content generated or stream failed early for conversation ID: {$chatData['conversation']->id}. Chat not saved.");
                }
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate, max-age=0',
            'Pragma'            => 'no-cache',
            'Expires'           => '0',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}
