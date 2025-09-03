<?php

namespace App\Http\Requests\Tenant;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Tenant $tenant */
        $tenant   = $this->route('tenant');
        $tenantId = $tenant?->id;
        $userId   = optional($tenant->user)->id;

        return [
            // Top‑level tenant fields
            'business_name' => ['sometimes', 'string', 'max:255'],
            'industry'      => ['sometimes', 'string', 'max:255'],
            'country'       => ['sometimes', 'string', 'size:2'],
            'language'      => ['sometimes', 'string', 'size:2'],
            'phone'         => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tenants', 'phone')->ignore($tenantId),
            ],
            'domain'          => ['sometimes', 'string', 'max:255'],
            'user.id'         => ['required', 'exists:users,id'],
            'user.first_name' => ['sometimes', 'string', 'max:255'],
            'user.last_name'  => ['sometimes', 'string', 'max:255'],
            'user.email'      => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
        ];
    }
}
