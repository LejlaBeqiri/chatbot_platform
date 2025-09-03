<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->uuid,
            'name'             => $this->name,
            'file_name'        => $this->file_name,
            'size'             => $this->size,
            'mime_type'        => $this->mime_type,
            'embedding_status' => $this->getCustomProperty('embedding_status'),
            'created_at'       => $this->created_at,
        ];
    }
}
