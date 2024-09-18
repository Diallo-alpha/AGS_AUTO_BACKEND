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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->string('video');
            $table->unsignedBigInteger('formation_id');
            $table->unsignedBigInteger('ressource_id');
            $table->foreign('formation_id')->references('id')->on('formations')->onDelete('cascade');
            $table->foreign('ressource_id')->references('id')->on('ressources')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
