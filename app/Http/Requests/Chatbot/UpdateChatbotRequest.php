<?php

namespace App\Http\Requests\Chatbot;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChatbotRequest extends FormRequest
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
            'name'                          => 'sometimes|string|max:255',
            'description'                   => 'nullable|string|max:255',
            'temperature'                   => 'sometimes|numeric|between:0,1',
            'ai_model_id'                   => 'nullable|string',
            'chatbot_system_prompt'         => 'sometimes|array',
            'chatbot_system_prompt.context' => 'nullable|string|max:500',
            'chatbot_system_prompt.rules'   => 'nullable|array',
            'chatbot_system_prompt.rules.*' => 'nullable|string|max:255',
        ];
    }
}
