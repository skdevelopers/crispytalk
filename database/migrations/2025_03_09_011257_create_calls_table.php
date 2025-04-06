<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates a calls table to store call records.
     */
    public function up()
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caller_id');  // The user initiating the call
            $table->unsignedBigInteger('callee_id');  // The user receiving the call
            $table->enum('call_type', ['audio', 'video']);
            $table->enum('status', ['pending', 'accepted', 'rejected', 'ended'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('caller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('callee_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
}
