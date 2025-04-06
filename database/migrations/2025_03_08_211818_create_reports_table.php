<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * This creates the reports table with:
     * - createdAt: string (or timestamp)
     * - reportBy: unsignedBigInteger (refers to the user who reported)
     * - reportTo: unsignedBigInteger (refers to the user being reported; nullable if not applicable)
     * - text: text (report details)
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('createdAt')->nullable();
            $table->unsignedBigInteger('reportBy');
            $table->unsignedBigInteger('reportTo')->nullable();
            $table->text('text');
            $table->timestamps();

            $table->foreign('reportBy')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reportTo')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the reports table.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
}
