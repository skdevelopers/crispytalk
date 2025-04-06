<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the comments table with:
     * - content: text (the comment itself)
     * - timestamp: string (or use a proper timestamp field)
     * - user_id: unsignedBigInteger (refers to the user who commented)
     * - userName: string (the user’s name snapshot)
     * - userImage: string (the URL of the user’s profile image)
     * - post_id: unsignedBigInteger (refers to the related post)
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->string('timestamp')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('userName');
            $table->string('userImage')->nullable();
            $table->unsignedBigInteger('post_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the comments table.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
}
