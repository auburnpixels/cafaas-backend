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
            $table->foreignUuid('prize_id')->nullable()->after('competition_id')->constrained('prizes')->onDelete('cascade');
            $table->index('prize_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draw_events', function (Blueprint $table) {
            $table->dropForeign(['prize_id']);
            $table->dropIndex(['prize_id']);
            $table->dropColumn('prize_id');
        });
    }
};
