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
        Schema::table('competitions', function (Blueprint $table) {
            // Drop the existing unique constraint on external_id
            $table->dropUnique(['external_id']);
            
            // Add a unique constraint on the combination of operator_id and external_id
            $table->unique(['operator_id', 'external_id'], 'competitions_operator_external_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('competitions_operator_external_unique');
            
            // Restore the unique constraint on external_id alone
            $table->unique('external_id');
        });
    }
};

