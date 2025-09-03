<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'tenant'       => new TenantResource($this->whenLoaded('tenant')),
            'chatbot'      => new ChatbotResource($this->whenLoaded('chatbot')),
            'user_message' => $this->user_message,
            'bot_response' => $this->bot_response,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
