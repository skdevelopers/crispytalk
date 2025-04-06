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
        // Drop the foreign keys referencing users.id
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']); // Adjust this to match your foreign key name
        });

        // Drop the current id column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        // Add the new bigIncrements id column
        Schema::table('users', function (Blueprint $table) {
            $table->bigIncrements('id')->first();
        });

        // Recreate the foreign key with the new bigint id
        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the process in case of rollback
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->uuid('id')->first();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
