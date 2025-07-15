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
        Schema::table('gamertags', function (Blueprint $table) {
            // Drop the problematic unique constraint that includes is_primary
            $table->dropUnique(['user_id', 'platform', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gamertags', function (Blueprint $table) {
            // Restore the constraint if needed
            $table->unique(['user_id', 'platform', 'is_primary']);
        });
    }
};
