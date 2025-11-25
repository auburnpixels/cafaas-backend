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
        Schema::create('draw_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained('competitions')->onDelete('cascade');
            $table->foreignUuid('prize_id')->nullable()->constrained('prizes')->onDelete('set null');
            $table->string('draw_id');
            $table->timestamp('drawn_at_utc');
            $table->integer('total_entries');
            $table->foreignUuid('selected_entry_id')->nullable()->constrained('tickets')->onDelete('set null');
            $table->string('rng_seed_or_hash');
            $table->string('signature_hash', 64);
            $table->string('previous_signature_hash', 64)->nullable();
            $table->timestamps();

            $table->index('competition_id');
            $table->index('draw_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draw_audits');
    }
};


