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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('game')->nullable(); // e.g., "Halo", "Call of Duty", etc.
            $table->string('platform')->nullable(); // e.g., "steam", "xbox_live", etc.
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_public')->default(true);
            $table->integer('max_members')->default(50);
            $table->string('avatar')->nullable();
            $table->json('settings')->nullable(); // Additional group settings
            $table->timestamps();

            $table->index(['game', 'platform']);
            $table->index(['is_public', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
