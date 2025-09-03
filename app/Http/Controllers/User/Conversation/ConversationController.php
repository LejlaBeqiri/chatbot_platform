<?php

namespace App\Http\Controllers\User\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatResource;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $chatbot_id = $request->validate([
            'chatbot_id' => 'required|exists:chatbots,id',
            'limit'      => 'integer|min:1|max:10',
        ])['chatbot_id'];

        return $this->success(ConversationResource::collection(
            Conversation::when(! $this->authUser()->hasRole('admin'), function ($query) {
                $query->where('tenant_id', $this->authUser()->tenant->id);
            })
                ->where('chatbot_id', $chatbot_id)
                ->whereNotNull('user_identifier')
                ->limit($request->input('limit', 10))
                ->orderBy('created_at', 'desc')
                ->get()
        ));
    }

    /**
     * Display the specified resource.
     */
    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return $this->success(new ConversationResource($conversation));
    }

    public function chat_messages(Conversation $conversation)
    {
        $this->authorize('view', $conversation);
        $chatMessages = ChatResource::collection($conversation->chats);

        return $this->success($chatMessages);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Conversation $conversation): JsonResponse
    {
        $this->authorize('delete', $conversation);
        $conversation->delete();
        $conversation->chats()->delete();

        return $this->success(data: null, message: 'Conversation deleted successfully');
    }
}
