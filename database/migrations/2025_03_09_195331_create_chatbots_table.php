<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chatbots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('ai_model_id')->nullable();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->text('chatbot_system_prompt')->nullable();
            $table->foreignId('knowledge_base_id')->nullable()->constrained('knowledge_bases');
            $table->timestamps();
        });
    }
};
