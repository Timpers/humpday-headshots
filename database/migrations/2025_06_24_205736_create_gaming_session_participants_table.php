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
        Schema::create('gaming_session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gaming_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['joined', 'left', 'kicked'])->default('joined');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->text('notes')->nullable(); // Participant notes or host notes about participant
            $table->timestamps();

            $table->unique(['gaming_session_id', 'user_id']);
            $table->index(['gaming_session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaming_session_participants');
    }
};
