<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_meetings_table.php
public function up()
{
    Schema::create('meetings', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('description')->nullable();
        $table->dateTime('date');
        $table->string('location');
        $table->enum('status', ['à venir', 'en cours', 'terminée'])->default('à venir');

        // Clé étrangère vers l'organisateur
        $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
