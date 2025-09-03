<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatbotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'ulid'                  => $this->ulid,
            'description'           => $this->description,
            'temperature'           => $this->temperature,
            'is_active'             => $this->is_active,
            'chatbot_system_prompt' => $this->chatbot_system_prompt,
            'prompt_components'     => json_decode($this->getRawOriginal('chatbot_system_prompt'), true),
            'created_at'            => $this->created_at,
        ];
    }
}
