<?php

declare(strict_types=1);

use App\Models\DrawEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Process all existing draw events in sequence order and calculate their hashes
        $this->backfillChainData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset the chain fields for all events
        DB::table('draw_events')->update([
            'previous_hash' => null,
            'current_hash' => null,
            'is_chained' => false,
        ]);
    }

    /**
     * Backfill chain data for all existing draw events
     */
    protected function backfillChainData(): void
    {
        $previousHash = null;
        $processedCount = 0;

        // Process events in chunks to handle large datasets efficiently
        DrawEvent::orderBy('sequence', 'asc')
            ->chunk(100, function ($events) use (&$previousHash, &$processedCount) {
                foreach ($events as $event) {
                    // Build hash input using the same algorithm as ProcessChainEvent
                    $hashInput = json_encode([
                        'id' => $event->id,
                        'sequence' => $event->sequence,
                        'event_type' => $event->event_type,
                        'payload' => $event->event_payload,
                        'competition_id' => $event->competition_id,
                        'operator_id' => $event->operator_id,
                        'prize_id' => $event->prize_id,
                        'actor_type' => $event->actor_type,
                        'actor_id' => $event->actor_id,
                        'ip_address' => $event->ip_address,
                        'user_agent' => $event->user_agent,
                        'previous_hash' => $previousHash,
                        'created_at' => $event->created_at->toIso8601String(),
                    ]);

                    $currentHash = hash('sha256', $hashInput);

                    // Update the event with chain data
                    DB::table('draw_events')
                        ->where('id', $event->id)
                        ->update([
                            'previous_hash' => $previousHash,
                            'current_hash' => $currentHash,
                            'is_chained' => true,
                        ]);

                    $previousHash = $currentHash;
                    $processedCount++;

                    if ($processedCount % 100 === 0) {
                        echo "Processed {$processedCount} events...\n";
                    }
                }
            });

        echo "Backfill complete! Processed {$processedCount} events.\n";
    }
};
