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
            $table->foreignUuid('operator_id')->nullable()->after('competition_id')->constrained('operators')->onDelete('cascade');
            $table->index('operator_id');
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->foreignUuid('operator_id')->nullable()->after('competition_id')->constrained('operators')->onDelete('cascade');
            $table->index('operator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draw_events', function (Blueprint $table) {
            $table->dropForeign(['operator_id']);
            $table->dropColumn('operator_id');
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['operator_id']);
            $table->dropColumn('operator_id');
        });
    }
};
