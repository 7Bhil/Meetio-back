<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_meeting_user_table.php
public function up()
{
    Schema::create('meeting_user', function (Blueprint $table) {
        $table->id();
        $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->timestamps();

        // Empêche les doublons
        $table->unique(['meeting_id', 'user_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_user');
    }
};
