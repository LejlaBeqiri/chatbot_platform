<?php

namespace App\Http\Controllers\Admin\Chatbot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\StoreChatbotRequest;
use App\Http\Requests\Chatbot\UpdateChatbotRequest;
use App\Models\Chatbot;

class AdminChatbotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $chatbots = Chatbot::paginate($this->defaultPerPage);

        return $this->success($chatbots);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(StoreChatbotRequest $request)
    {
        $chatbot = Chatbot::create($request->validated());

        return $this->success($chatbot, 'Chatbot created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Chatbot $chatbot)
    {
        return $this->success($chatbot);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChatbotRequest $request, Chatbot $chatbot)
    {
        $chatbot->update($request->validated());

        return $this->success($chatbot, 'Chatbot updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Chatbot $chatbot)
    {
        $chatbot->delete();

        return $this->success(null, 'Chatbot deleted successfully.');
    }
}
