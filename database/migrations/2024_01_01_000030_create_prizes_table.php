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
        Schema::create('prizes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained('competitions')->onDelete('cascade');
            $table->string('external_id');
            $table->string('name');
            $table->integer('draw_order')->default(1);
            $table->timestamps();

            $table->unique(['competition_id', 'external_id'], 'prizes_competition_external_unique');
            $table->index('competition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prizes');
    }
};
