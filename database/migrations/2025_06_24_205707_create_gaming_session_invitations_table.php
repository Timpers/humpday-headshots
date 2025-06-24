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
        Schema::create('gaming_session_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gaming_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('invited_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('invited_group_id')->nullable()->constrained('groups')->onDelete('cascade');
            $table->foreignId('invited_by_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['gaming_session_id', 'status']);
            $table->index(['invited_user_id', 'status']);
            $table->index(['invited_group_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaming_session_invitations');
    }
};
