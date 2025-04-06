<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nickName')->nullable();
            $table->string('profileUrl')->nullable();
            $table->string('phone')->nullable();
            $table->string('bgUrl')->nullable();
            $table->text('bio')->nullable();
            $table->json('likes')->nullable();
            $table->json('followers')->nullable();
            $table->json('following')->nullable();
            $table->json('savedPosts')->nullable();
            $table->json('blocks')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->boolean('userStatus')->default(true);
            $table->string('blockStatus')->default('active');
            $table->boolean('isOnline')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nickName', 'profileUrl', 'phone', 'bgUrl', 'bio', 'likes',
                'followers', 'following', 'savedPosts', 'blocks', 'instagram',
                'facebook', 'userStatus', 'blockStatus', 'isOnline',
            ]);
        });
    }
};
