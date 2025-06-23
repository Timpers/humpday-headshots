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
        Schema::create('gamertags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('platform', ['steam', 'xbox_live', 'playstation_network', 'nintendo_online', 'battlenet']);
            $table->string('gamertag');
            $table->string('display_name')->nullable(); // Optional display name
            $table->boolean('is_public')->default(true); // Whether the gamertag is public
            $table->boolean('is_primary')->default(false); // Whether this is the primary gamertag for this platform
            $table->json('additional_data')->nullable(); // For storing additional platform-specific data
            $table->timestamps();

            // Ensure a user can only have one primary gamertag per platform
            $table->unique(['user_id', 'platform', 'is_primary']);
            // Ensure a user can't have duplicate gamertags for the same platform
            $table->unique(['user_id', 'platform', 'gamertag']);
            // Index for faster queries
            $table->index(['platform', 'gamertag']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gamertags');
    }
};
