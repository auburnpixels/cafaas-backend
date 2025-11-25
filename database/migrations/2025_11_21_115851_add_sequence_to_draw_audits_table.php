<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('draw_audits', function (Blueprint $table) {
            $table->unsignedBigInteger('sequence')->nullable()->after('id');
            $table->index('sequence');
        });

        // Backfill sequence numbers for existing records
        // Order by drawn_at_utc and created_at to maintain chronological order
        $audits = DB::table('draw_audits')
            ->orderBy('drawn_at_utc', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $sequence = 1;
        foreach ($audits as $audit) {
            DB::table('draw_audits')
                ->where('id', $audit->id)
                ->update(['sequence' => $sequence]);
            $sequence++;
        }

        // Make the column non-nullable after backfill
        Schema::table('draw_audits', function (Blueprint $table) {
            $table->unsignedBigInteger('sequence')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draw_audits', function (Blueprint $table) {
            $table->dropIndex(['sequence']);
            $table->dropColumn('sequence');
        });
    }
};
