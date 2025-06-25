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
        Schema::table('gaming_session_messages', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('gaming_session_messages', function (Blueprint $table) {
            $table->enum('type', ['text', 'system', 'announcement'])->default('text')->after('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gaming_session_messages', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('gaming_session_messages', function (Blueprint $table) {
            $table->enum('type', ['message', 'system', 'announcement'])->default('message')->after('message');
        });
    }
};
