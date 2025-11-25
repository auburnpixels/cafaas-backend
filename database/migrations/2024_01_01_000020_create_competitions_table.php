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
        Schema::create('competitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operator_id')->nullable()->index()->constrained('operators')->onDelete('set null');
            $table->string('external_id')->nullable()->unique();
            $table->string('title');
            $table->string('status')->default('unpublished')->index();
            $table->integer('ticket_quantity')->nullable();
            $table->timestamp('draw_at')->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'external_id']);
            $table->index(['status', 'draw_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};


