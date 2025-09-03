<?php

use App\Http\Controllers\Admin\Chatbot\AdminChatbotController;
use App\Http\Controllers\Admin\Tenant\AdminTenantController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('tenants', [AdminTenantController::class, 'store']);
    Route::get('tenants', [AdminTenantController::class, 'index']);
    Route::get('tenants/{tenant}', [AdminTenantController::class, 'show']);
    Route::put('tenants/{tenant}', [AdminTenantController::class, 'update']);
    Route::delete('tenants/{tenant}', [AdminTenantController::class, 'destroy']);

    Route::get('chatbots', [AdminChatbotController::class, 'index']);
    Route::post('chatbots', [AdminChatbotController::class, 'store']);
    Route::get('chatbots/{chatbot}', [AdminChatbotController::class, 'show']);
    Route::put('chatbots/{chatbot}', [AdminChatbotController::class, 'update']);
    Route::delete('chatbots/{chatbot}', [AdminChatbotController::class, 'destroy']);
});
