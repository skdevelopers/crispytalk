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
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('mediaUrl');  // Remove media URL
            $table->dropColumn('mediaType'); // Remove media type
            $table->boolean('is_video')->default(false); // ✅ Add is_video column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('mediaUrl')->nullable();
            $table->string('mediaType')->nullable();
            $table->dropColumn('is_video');
        });
    }
};
