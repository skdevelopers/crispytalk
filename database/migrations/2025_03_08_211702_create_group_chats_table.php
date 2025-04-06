<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the group_chats table with:
     * - admin: unsignedBigInteger (refers to the group admin)
     * - createdAt: string (or timestamp)
     * - groupImage: string (URL to group image)
     * - groupName: string (name of the group)
     * - members: JSON (array of member user IDs)
     */
    public function up(): void
    {
        Schema::create('group_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin');
            $table->string('createdAt')->nullable();
            $table->string('groupImage')->nullable();
            $table->string('groupName');
            $table->json('members');
            $table->timestamps();

            $table->foreign('admin')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the group_chats table.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_chats');
    }
}
