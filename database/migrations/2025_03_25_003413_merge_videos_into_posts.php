<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // Ensure required columns exist in posts table
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'filename')) {
                $table->string('filename')->nullable();
            }
            if (!Schema::hasColumn('posts', 'path')) {
                $table->string('path')->nullable();
            }
            if (!Schema::hasColumn('posts', 'transcoded_paths')) {
                $table->json('transcoded_paths')->nullable();
            }
            if (!Schema::hasColumn('posts', 'status')) {
                $table->string('status')->default('processing');
            }
        });

        // Move data from videos to posts (Fix: Use "mediaUrl" with quotes)
        DB::statement("
            INSERT INTO posts (id, filename, path, transcoded_paths, status, views, user_id, created_at, updated_at, audience, thumbnail, \"mediaUrl\", \"mediaType\")
            SELECT id, filename, path, transcoded_paths, status, views, user_id, created_at, updated_at, audience, thumbnail, path AS \"mediaUrl\", 'video' AS \"mediaType\" FROM videos
            ON CONFLICT (id) DO NOTHING;
        ");

        // Drop videos table after migration
        Schema::dropIfExists('videos');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore videos table
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('path');
            $table->json('transcoded_paths')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('status')->default('processing');
            $table->bigInteger('views')->default(0);
            $table->bigInteger('user_id');
            $table->string('audience')->default('public');
            $table->timestamps();
        });

        // Restore video data from posts (Fix: Use "mediaUrl")
        DB::statement("
            INSERT INTO videos (id, filename, path, transcoded_paths, status, views, user_id, created_at, updated_at, audience, thumbnail)
            SELECT id, filename, path, transcoded_paths, status, views, user_id, created_at, updated_at, audience, thumbnail FROM posts WHERE \"mediaType\" = 'video'
            ON CONFLICT (id) DO NOTHING;
        ");

        // Drop extra columns from posts
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['filename', 'path', 'transcoded_paths', 'status']);
        });
    }
};
