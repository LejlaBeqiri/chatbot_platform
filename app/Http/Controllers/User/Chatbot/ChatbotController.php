<?php

namespace App\Http\Controllers\User\Chatbot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\StoreChatbotRequest;
use App\Http\Requests\Chatbot\UpdateChatbotRequest;
use App\Http\Resources\ChatbotResource;
use App\Models\Chatbot;
use App\Values\RoleValues;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $query = Chatbot::query();

        if (! $this->authUser()->hasRole(RoleValues::Admin->value)) {
            $query->where('tenant_id', $this->authUser()->tenant->id);
        }

        return $this->success(ChatbotResource::collection($query->paginate($this->defaultPerPage))->response()->getData(true));
    }

    /**
     * Display the specified resource.
     */
    public function show(Chatbot $chatbot): JsonResponse
    {
        $this->authorize('view', $chatbot);

        return $this->success(new ChatbotResource($chatbot));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreChatbotRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['chatbot_system_prompt'] = [
            'context' => $validated['chatbot_system_prompt']['context'] ?? 'Default chatbot context: Provide clear, concise answers.',
            'rules'   => ! empty($validated['chatbot_system_prompt']['rules']) ? $validated['chatbot_system_prompt']['rules'] : ['Always be polite and professional.'],
        ];

        $chatbot = Chatbot::create($validated);

        return $this->success(new ChatbotResource($chatbot));
    }

    public function update(UpdateChatbotRequest $request, Chatbot $chatbot): JsonResponse
    {
        $this->authorize('update', $chatbot);

        $validated = $request->validated();

        if (isset($validated['chatbot_system_prompt'])) {
            $components = $validated['chatbot_system_prompt'];
            $context    = $components['context'] ?? 'Default chatbot context: Provide clear, concise answers.';
            $rules      = ! empty($components['rules']) ? $components['rules'] : ['Always be polite and professional.'];

            $validated['chatbot_system_prompt'] = [
                'context' => $context,
                'rules'   => $rules,
            ];
        }

        $chatbot->update($validated);

        return $this->success(new ChatbotResource($chatbot));
    }

    public function destroy(Chatbot $chatbot): JsonResponse
    {
        $this->authorize('delete', $chatbot);
        $chatbot->delete();

        return $this->success(data: null, message: 'Chatbot deleted successfully');
    }

    public function setActiveStatus(Chatbot $chatbot, Request $request): JsonResponse
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $this->authorize('update', $chatbot);

        $chatbot->is_active = $request->boolean('is_active');
        $chatbot->save();

        return $this->success(data: null, message: 'Chatbot status updated successfully');
    }
}
