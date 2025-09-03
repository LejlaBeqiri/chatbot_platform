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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->default('');
            $table->string('industry')->nullable();
            $table->string('domain')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('language', 2)->nullable();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->string('phone')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }
};
