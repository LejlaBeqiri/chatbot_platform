<?php

namespace App\Http\Controllers\Public\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Chat\AskQuestionRequest;
use App\Models\Chatbot;
use App\Models\Tenant;
use App\Services\AuthService;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PublicChatController extends Controller
{
    protected Tenant $tenant;

    protected ChatService $chatService;

    public function __construct(Request $request, AuthService $auth, ChatService $chatService)
    {
           

        $this->tenant      = $auth->tenantFromRequest($request);
        $this->chatService = $chatService;
    }

    public function askQuestion(AskQuestionRequest $request, $chatbot_id)
    {
        $chatbot       = Chatbot::where('ulid', $chatbot_id)->firstOrFail();
        $validatedData = $request->validated();

        try {
            $chatData = $this->chatService->askQuestion(
                $validatedData['question'],
                $chatbot,
                $this->tenant->id,
                $validatedData['user_identifier']
            );

            $openAIDecryptedApiKey = Crypt::decryptString(
                $this->tenant
                    ->apiKeys()
                    ->where('provider', 'OPENAI')
                    ->first()->key
            );

            Log::info('here');
            return $this->chatService->handleChatStream($chatData, $openAIDecryptedApiKey);
        } catch (Throwable $e) {
            Log::error('Failed to initiate chat stream: '.$e->getMessage(), ['exception' => $e]);
            if ($e->getCode() === 403 || $e->getCode() === 404) {
                return $this->error($e->getMessage(), $e->getCode());
            }

            return $this->error('Failed to start the chat stream.', 500);
        }
    }

    public function getUserSessionIdentifier(string $chatbot_id): JsonResponse
    {
        $chatbot = $this->tenant
            ->chatbots()
            ->where('ulid', $chatbot_id)
            ->first();

        if (! $chatbot) {
            return $this->error('Unauthorized: Invalid chatbot ID.', 401);
        }

        $userIdentifier = Str::ulid();

        return $this->success(data: $userIdentifier);
    }
}
