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
        Schema::create('free_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('competition_id')->constrained('competitions')->onDelete('cascade');
            $table->string('user_reference')->nullable();
            $table->text('reason')->nullable();
            $table->string('submitted_by')->nullable(); // 'operator', 'manual', 'postal'
            $table->timestamps();

            $table->index('competition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('free_entries');
    }
};


