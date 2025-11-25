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
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable()->unique();
            $table->foreignUuid('competition_id')->constrained('competitions')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('checkout_id')->nullable();
            $table->integer('number');
            $table->boolean('free')->default(false);
            $table->timestamps();

            $table->index(['competition_id', 'number'], 'tickets_competition_number_index');
            $table->index('user_id', 'tickets_user_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
