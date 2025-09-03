<?php

namespace App\Http\Requests\Chatbot;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatbotRequest extends FormRequest
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
            'name'                          => 'required|string|max:255',
            'description'                   => 'nullable|string|max:255',
            'temperature'                   => 'required|numeric|between:0,1',
            'chatbot_system_prompt'         => 'required|array',
            'chatbot_system_prompt.context' => 'required|string|max:500',
            'chatbot_system_prompt.rules'   => 'required|array',
            'chatbot_system_prompt.rules.*' => 'required|string|max:255',
            'tenant_id'                     => 'integer|exists:tenants,id',
            'knowledge_base_id'             => 'integer|exists:knowledge_bases,id',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'tenant_id'         => auth()->user()->tenant->id,
            'knowledge_base_id' => auth()->user()->tenant->knowledge_base->id,
        ]);
    }
}
