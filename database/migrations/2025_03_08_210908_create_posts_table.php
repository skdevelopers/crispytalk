<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the posts table with the following columns:
     * - timeStamp: string (you may choose to use a timestamp field if preferred)
     * - title: string (title of the post)
     * - audience: string (e.g., public, friends, etc.)
     * - filterIndex: integer (for filtering posts)
     * - mediaUrl: string (URL of the media file)
     * - mediaType: string (e.g., video, image)
     * - thumbnail: string (URL of the thumbnail image)
     * - likes: JSON column to store an array of user IDs who liked the post
     * - saved: JSON column to store an array of user IDs who saved the post
     * - views: integer (number of views)
     * - user_id: unsignedBigInteger (foreign key referencing the user who uploaded the post)
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('timeStamp')->nullable();
            $table->string('title');
            $table->string('audience');
            $table->integer('filterIndex')->default(0);
            $table->string('mediaUrl');
            $table->string('mediaType');
            $table->string('thumbnail')->nullable();
            $table->json('likes')->nullable();
            $table->json('saved')->nullable();
            $table->integer('views')->default(0);
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the posts table.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
}
