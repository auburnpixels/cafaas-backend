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
            $table->string('previous_hash', 64)->nullable()->after('event_hash');
            $table->string('current_hash', 64)->nullable()->after('previous_hash');
            $table->boolean('is_chained')->default(false)->after('current_hash');
            
            $table->index('is_chained');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draw_events', function (Blueprint $table) {
            $table->dropIndex(['is_chained']);
            $table->dropColumn(['previous_hash', 'current_hash', 'is_chained']);
        });
    }
};
