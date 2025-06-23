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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // IGDB Information
            $table->unsignedBigInteger('igdb_id')->nullable();
            $table->string('name');
            $table->text('summary')->nullable();
            $table->string('slug')->nullable();
            $table->json('cover')->nullable(); // Store cover image data
            $table->json('screenshots')->nullable(); // Store screenshot data
            $table->date('release_date')->nullable();
            $table->json('genres')->nullable(); // Store genre names/ids
            $table->json('platforms')->nullable(); // Store platform names/ids from IGDB
            $table->decimal('rating', 3, 1)->nullable(); // IGDB rating
            $table->string('status')->default('owned'); // owned, wishlist, playing, completed
            
            // User-specific data
            $table->string('platform')->nullable(); // User's specific platform
            $table->decimal('user_rating', 2, 1)->nullable(); // User's personal rating (1-10)
            $table->text('notes')->nullable(); // User's personal notes
            $table->integer('hours_played')->nullable();
            $table->date('date_purchased')->nullable();
            $table->decimal('price_paid', 8, 2)->nullable();
            $table->boolean('is_digital')->default(true);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_favorite')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'platform']);
            $table->unique(['user_id', 'igdb_id', 'platform']); // Prevent duplicate games per platform per user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
