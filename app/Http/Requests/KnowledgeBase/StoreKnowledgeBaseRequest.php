<?php

namespace App\Http\Requests\KnowledgeBase;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeBaseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'files'       => 'array',
            'files.*'     => 'required|file|max:5000',
            'tenant_id'   => 'required|integer|exists:tenants,id',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'tenant_id' => auth()->user()->tenant->id,
        ]);
    }
}
