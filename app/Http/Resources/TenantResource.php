<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'business_name' => $this->business_name,
            'industry'      => $this->industry,
            'logo_url'      => $this->logo_url,
            'country'       => $this->country,
            'language'      => $this->language,
            'phone'         => $this->phone,
            'user'          => new UserResource($this->user),
            'domain'        => $this->domain,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
