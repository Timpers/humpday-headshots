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
        Schema::create('gaming_session_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gaming_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->enum('type', ['message', 'system', 'announcement'])->default('message');
            $table->json('metadata')->nullable(); // For future extensibility (attachments, reactions, etc.)
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            $table->index(['gaming_session_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaming_session_messages');
    }
};
