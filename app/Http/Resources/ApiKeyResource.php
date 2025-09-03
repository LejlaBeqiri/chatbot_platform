<?php

namespace App\Http\Resources;

use App\Values\ApiKeyProvidersValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class ApiKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $maskedKey = null;
        $fullKey   = null;

        if ($this->key !== null) {
            try {
                $decryptedKey = Crypt::decryptString($this->key);
                $keyLength    = strlen($decryptedKey);

                if ($this->provider === ApiKeyProvidersValues::PLATFORM->value) {
                    $maskedKey = $decryptedKey;
                } elseif (is_string($decryptedKey) && $keyLength >= 3) {
                    $lastThree = substr($decryptedKey, -3);
                    $maskedKey = str_repeat('*', 9).$lastThree;
                } elseif (is_string($decryptedKey) && $keyLength > 0) {
                    $maskedKey = str_repeat('*', $keyLength);
                } else {
                    $maskedKey = '';
                }
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $maskedKey = 'Invalid Key';
            }
        } else {
            $maskedKey = null;
        }

        return [
            'id'         => $this->id,
            'provider'   => $this->provider,
            'key'        => $maskedKey,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
