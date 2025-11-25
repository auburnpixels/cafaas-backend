<?php

namespace App\Console\Commands;

use App\Models\DrawEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * @class TestLog
 */
class BackfillChainDataForExistingChainDrawEvents extends Command
{
    /**
     * @var string
     */
    protected $signature = 'chain-data:backfill';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
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
                        $this->info("Processed {$processedCount} events...");
                        $this->newLine();
                    }
                }
            });

        $this->info("Backfill complete! Processed {$processedCount} events.");
    }
}
