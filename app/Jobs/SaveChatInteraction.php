<?php

namespace App\Jobs;

use App\Models\Chat; // Make sure this namespace is correct
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable; // Import Throwable for exception type hint

class SaveChatInteraction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  string  $userMessage  The original user question saved
     * @param  string  $botResponse  The full accumulated bot response
     */
    public function __construct(
        protected int $conversationId,
        protected int $chatbotId,
        protected int $tenantId,
        protected string $userMessage,
        protected string $botResponse,
        protected ?int $embeddingId = null
    ) {}

    public function handle(): void
    {
        try {

            if (empty(trim($this->botResponse))) {
                Log::warning("Attempted to save chat with empty bot response for conversation ID: {$this->conversationId}");

                return;
            }

            Chat::create([
                'conversation_id' => $this->conversationId,
                'chatbot_id'      => $this->chatbotId,
                'tenant_id'       => $this->tenantId,
                'user_message'    => $this->userMessage,
                'bot_response'    => $this->botResponse,
                'embedding_id'    => $this->embeddingId,
            ]);
        } catch (Throwable $e) {
            Log::error("Failed to save chat interaction for conversation ID: {$this->conversationId}. Error: ".$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
