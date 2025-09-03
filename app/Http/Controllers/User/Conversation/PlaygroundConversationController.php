<?php

namespace App\Http\Controllers\User\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatResource;
use App\Http\Resources\ConversationResource;
use App\Models\Chat;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PlaygroundConversationController extends Controller
{
    public function __construct(protected User $user)
    {
        $this->user = $this->authUser()->load('tenant');
    }

    /**
     * Display the specified resource.
     */
    public function get_tenant_conversation($chatbot_id): JsonResponse
    {
        $conversation = Conversation::where('tenant_id', $this->authUser()->tenant->id)
            ->where('chatbot_id', $chatbot_id)
            ->whereNull('user_identifier')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'tenant_id'       => $this->authUser()->tenant->id,
                'chatbot_id'      => $chatbot_id,
                'user_identifier' => null,
            ]);
        }

        return $this->success(new ConversationResource($conversation));
    }

    public function chat_messages(Conversation $conversation)
    {
        $this->authorize('view', $conversation);
        $chatMessages = ChatResource::collection($conversation->chats);

        return $this->success($chatMessages);
    }

    public function destroy(): JsonResponse
    {
        Chat::whereHas('conversation', function ($query) {
            $query->where('tenant_id', $this->user->tenant->id);
        })->delete();

        return $this->success('All conversations deleted successfully.');
    }
}
