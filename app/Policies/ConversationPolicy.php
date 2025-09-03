<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $chatbot): bool
    {
        return $user->id === $chatbot->tenant->user_id;
    }

    public function update(User $user, Conversation $chatbot): bool
    {
        return $user->id === $chatbot->tenant->user_id;
    }

    public function delete(User $user, Conversation $chatbot): bool
    {
        return $user->id === $chatbot->tenant->user_id;
    }
}
