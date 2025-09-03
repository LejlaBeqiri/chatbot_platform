<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'tenant'          => new TenantResource($this->whenLoaded('tenant')),
            'chatbot'         => new ChatbotResource($this->whenLoaded('chatbot')),
            'chats'           => ChatResource::collection($this->chats),
            'user_identifier' => $this->user_identifier,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
