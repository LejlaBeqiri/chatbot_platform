<?php

namespace App\Policies;

use App\Models\Chatbot;
use App\Models\User;

class ChatbotPolicy
{
    public function view(User $user, Chatbot $chatbot): bool
    {
        return $user->id === $chatbot->tenant->user_id;
    }

    public function update(User $user, Chatbot $chatbot): bool
    {
        return $user->id === $chatbot->tenant->user_id;
    }

    public function delete(User $user, Chatbot $chatbot): bool
    {
        return $user->id === $chatbot->tenant->user_id;
    }
}
