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
        Schema::table('draw_events', function (Blueprint $table) {
            $table->foreignUuid('competition_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draw_events', function (Blueprint $table) {
            // Note: Reverting this might fail if there are null records
            $table->foreignUuid('competition_id')->nullable(false)->change();
        });
    }
};
