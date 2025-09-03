<?php

namespace App\Http\Resources;

use App\Values\MediaCollectionsValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeBaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'tenant'      => new TenantResource($this->tenant),
            'files'       => MediaResource::collection($this->getMedia(MediaCollectionsValues::TRAINING_DATA->value)),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
