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
        Schema::table('draw_audits', function (Blueprint $table) {
            $table->string('pool_hash', 64)->nullable()->after('rng_seed_or_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draw_audits', function (Blueprint $table) {
            $table->dropColumn('pool_hash');
        });
    }
};
