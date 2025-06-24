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
        Schema::create('gaming_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('game_name');
            $table->json('game_data')->nullable(); // Store IGDB game data
            $table->string('platform')->nullable();
            $table->datetime('scheduled_at');
            $table->integer('max_participants')->default(8);
            $table->enum('status', ['scheduled', 'active', 'completed', 'cancelled'])->default('scheduled');
            $table->enum('privacy', ['public', 'friends_only', 'invite_only'])->default('friends_only');
            $table->text('requirements')->nullable(); // Game requirements, skill level, etc.
            $table->timestamps();

            $table->index(['scheduled_at', 'status']);
            $table->index(['host_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaming_sessions');
    }
};
