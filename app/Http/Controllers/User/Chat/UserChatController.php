<?php

namespace App\Http\Controllers\User\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Chat\AskQuestionRequest;
use App\Http\Resources\ChatResource;
use App\Models\Chat;
use App\Models\Chatbot;
use App\Services\ChatService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class UserChatController extends Controller
{
    protected $user;

    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->user = $this->authUser()->load('tenant');

        if (! $this->user->tenant
            ->apiKeys()
            ->where('provider', 'OPENAI')
            ->first()->key) {

            $response = new StreamedResponse(function () {
                echo "event: missing_api_key\n";
                echo 'data: '.json_encode(['message' => 'Tenant is missing API key.'])."\n\n";
            }, 200, [
                'Content-Type'      => 'text/event-stream',
                'Cache-Control'     => 'no-cache, must-revalidate',
                'X-Accel-Buffering' => 'no',  // for nginx
            ]);

            throw new HttpResponseException($response);
        }
        $this->chatService = $chatService;
    }

    public function index(): JsonResource
    {
        $chats        = Chat::where('tenant_id', $this->user->tenant->id)->limit(10)->get();
        $chatResource = ChatResource::collection($chats);

        return $this->success($chatResource)->paginate($this->defaultPerPage);
    }

    public function askQuestion(AskQuestionRequest $request, $chatbot_id)
    {
        $chatbot       = Chatbot::where('ulid', $chatbot_id)->firstOrFail();
        $validatedData = $request->validated();
        $userQuestion  = $validatedData['question'];
        try {
            $chatData = $this->chatService->askQuestion(
                $userQuestion,
                $chatbot,
                $this->user->tenant->id,
                null,
                true
            );

            $openAIDecryptedApiKey = Crypt::decryptString(
                $this->user->tenant
                    ->apiKeys()
                    ->where('provider', 'OPENAI')
                    ->first()->key
            );

            return $this->chatService->handleChatStream($chatData, $openAIDecryptedApiKey);
        } catch (Throwable $e) {
            Log::error('Failed to initiate chat stream: '.$e->getMessage(), ['exception' => $e]);

            return $this->error('Failed to start the chat stream.', 500);
        }
    }
}
