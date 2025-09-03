<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\Chat\PublicChatController;
use App\Http\Controllers\User\Account\UserAccountController;
use App\Http\Controllers\User\ApiKey\ApiKeyController;
use App\Http\Controllers\User\Chat\UserChatController as TenantChatController;
use App\Http\Controllers\User\Chatbot\ChatbotController;
use App\Http\Controllers\User\Conversation\ConversationController;
use App\Http\Controllers\User\Conversation\PlaygroundConversationController;
use App\Http\Controllers\User\Dashboard\DashboardController;
use App\Http\Controllers\User\Embedding\EmbeddingController;
use App\Http\Controllers\User\KnowledgeBase\KnowledgeBaseController;
use App\Http\Controllers\User\Profile\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::post('login', LoginController::class)->name('login');

Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'user'], function () {

    Route::put('profile', [UserProfileController::class, 'update']);
    Route::get('statistics', [DashboardController::class, 'index']);
    Route::get('conversation-history', [DashboardController::class, 'conversationHistory']);

    Route::post('change-password', [UserAccountController::class, 'changePassword']);
    Route::post('logout', [LoginController::class, 'logout']);
    Route::post('chatbots', [ChatbotController::class, 'store']);
    Route::get('chatbots', [ChatbotController::class, 'index']);
    Route::get('chatbots/{chatbot}', [ChatbotController::class, 'show']);
    Route::put('chatbots/{chatbot}', [ChatbotController::class, 'update']);
    Route::delete('chatbots/{chatbot}', [ChatbotController::class, 'destroy']);
    Route::post('chatbots/{chatbot}/set-active-status', [ChatbotController::class, 'setActiveStatus']);

    Route::get('knowledge-bases', [KnowledgeBaseController::class, 'index']);
    Route::post('knowledge-bases', [KnowledgeBaseController::class, 'store']);
    Route::get('knowledge-bases/{knowledgeBase}', [KnowledgeBaseController::class, 'show']);
    Route::get('knowledge-bases/{knowledgeBase}/embeddings', [KnowledgeBaseController::class, 'getEmbeddings']);
    Route::post('knowledge-bases/{knowledgeBase}/embeddings', [KnowledgeBaseController::class, 'addEmbeddings']);
    Route::put('knowledge-bases/{knowledgeBase}', [KnowledgeBaseController::class, 'update']);
    Route::delete('knowledge-bases/{knowledgeBase}', [KnowledgeBaseController::class, 'destroy']);
    Route::get('knowledge-bases/download/{knowledgeBase}/{mediaId?}', [KnowledgeBaseController::class, 'download'])->name('knowledge-bases.download');
    Route::delete('knowledge-bases/delete-file/{knowledgeBase}/{mediaId?}', [KnowledgeBaseController::class, 'deleteFile'])->name('knowledge-bases.delete-file');
    Route::post('knowledge-bases/upload/{knowledgeBase}', [KnowledgeBaseController::class, 'uploadForEmbedding'])->name('knowledge-bases.upload-file');

    Route::post('/embeddings/process', [EmbeddingController::class, 'process_embeddings']);
    Route::delete('/embeddings/{knowledgeBase}/{embedding}', [EmbeddingController::class, 'deleteEmbedding']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::get('/conversations/tenant', [ConversationController::class, 'getConversationByTenantId']);
    Route::get('/conversations/{conversation}/chat-messages', [ConversationController::class, 'chat_messages']);
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy']);

    Route::get('/api-keys', [ApiKeyController::class, 'index']);
    Route::get('/api-keys/{apiKey}', [ApiKeyController::class, 'show']);
    Route::post('/api-keys', [ApiKeyController::class, 'store']);
    Route::post('/api-keys/platform-key', [ApiKeyController::class, 'generatePlatformAPIKey']);
    Route::put('/api-keys/{apiKey}', [ApiKeyController::class, 'update']);
    Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy']);

    Route::get('/playground/chatbot/{chatbot_id}', [PlaygroundConversationController::class, 'get_tenant_conversation']);
    Route::delete('/playground/conversations-messages', [PlaygroundConversationController::class, 'destroy']);

    // playGround chat
    Route::post('ask-questions/chatbot/{chatbot_id}', [TenantChatController::class, 'askQuestion']);
    Route::post('tenants/{tenant}/chat-messages', [TenantChatController::class, 'index']);
});

Route::post('ask-questions/chatbot/{chatbot_id}', [PublicChatController::class, 'askQuestion']);
Route::get('get-user-session-identifier/{chatbot_id}', [PublicChatController::class, 'getUserSessionIdentifier']);
