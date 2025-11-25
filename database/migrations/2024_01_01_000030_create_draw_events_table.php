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
        Schema::create('draw_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('sequence')->default(0);
            $table->foreignUuid('competition_id')->constrained('competitions')->onDelete('cascade');
            $table->string('event_type');
            $table->jsonb('event_payload')->nullable();
            $table->string('event_hash', 64);
            $table->string('previous_event_hash', 64)->nullable();
            $table->string('actor_type')->nullable();
            $table->string('actor_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

            $table->index(['competition_id', 'sequence']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draw_events');
    }
};


