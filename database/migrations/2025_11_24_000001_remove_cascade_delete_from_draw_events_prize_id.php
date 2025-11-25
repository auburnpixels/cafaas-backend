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
            // Drop the existing foreign key constraint with cascade delete
            $table->dropForeign(['prize_id']);
            
            // Re-add the foreign key WITHOUT cascade delete (no action)
            // This prevents cascade deletes and allows prizes to be soft deleted
            // while maintaining referential integrity for the audit trail
            $table->foreign('prize_id')
                ->references('id')
                ->on('prizes')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draw_events', function (Blueprint $table) {
            // Drop the restrict foreign key
            $table->dropForeign(['prize_id']);
            
            // Re-add the original cascade delete foreign key
            $table->foreign('prize_id')
                ->references('id')
                ->on('prizes')
                ->onDelete('cascade');
        });
    }
};

