<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user.first_name'      => ['required', 'string', 'max:255'],
            'user.last_name'       => ['required', 'string', 'max:255'],
            'user.email'           => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'user.password'        => ['required', 'string', 'min:8', 'confirmed'],
            'tenant.business_name' => ['required', 'string', 'max:255'],
            'tenant.industry'      => ['required', 'string', 'max:255'],
            'tenant.country'       => ['required', 'string', 'max:2'],
            'tenant.language'      => ['required', 'string', 'max:2'],
            'tenant.phone'         => ['required', 'string', 'max:255'],
        ];
    }
}
