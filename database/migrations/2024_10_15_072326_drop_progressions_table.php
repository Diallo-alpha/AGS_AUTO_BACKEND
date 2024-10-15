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
        Schema::table('progressions', function (Blueprint $table) {
            //
            Schema::dropIfExists('progressions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progressions', function (Blueprint $table) {
            //
            Schema::create('progressions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('formation_id');
                $table->unsignedBigInteger('user_id');
                $table->integer('pourcentage')->default(0);
                $table->boolean('terminer')->default(false);
                $table->foreign('formation_id')->references('id')->on('formations')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['formation_id', 'user_id']);
                $table->timestamps();
                $table->dropColumn('videos_regarder');
            });
        });
    }
};
