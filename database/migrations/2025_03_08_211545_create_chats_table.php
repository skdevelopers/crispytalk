<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the chats table with:
     * - createdAt: string (or timestamp)
     * - lastMessage: text (the last message sent in the chat)
     * - users: JSON (list of user IDs participating in the chat)
     */
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('createdAt')->nullable();
            $table->text('lastMessage')->nullable();
            $table->json('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the chats table.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
}
