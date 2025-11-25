<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @class CompetitionDrawAudit
 */
final class CompetitionDrawAudit extends Model
{
    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var string[]
     */
    protected $fillable = [
        'competition_id',
        'prize_id',
        'draw_id',
        'drawn_at_utc',
        'total_entries',
        'rng_seed_or_hash',
        'pool_hash',
        'selected_entry_id',
        'signature_hash',
        'previous_signature_hash',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'drawn_at_utc' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class, 'competition_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function winningTicket()
    {
        return $this->belongsTo(Ticket::class, 'selected_entry_id');
    }

    /**
     * Generate a signature hash for audit integrity.
     */
    public static function generateSignature(
        string $competitionId,
        string $drawId,
        string $drawnAtUtc,
        int $totalEntries,
        string $rngSeedOrHash,
        ?string $selectedEntryId = null,
        ?string $previousSignatureHash = null
    ): string {
        $data = implode('|', [
            $competitionId,
            $drawId,
            $drawnAtUtc,
            $totalEntries,
            $selectedEntryId,
            $rngSeedOrHash,
            $previousSignatureHash ?? '',
        ]);

        return hash('sha256', $data);
    }

    /**
     * Get the signature hash of the most recent audit.
     */
    public static function getLastAuditSignature(): ?string
    {
        $lastAudit = self::orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $lastAudit?->signature_hash;
    }

    /**
     * Verify the integrity of the audit chain.
     *
     * @return array Results of the verification
     */
    public static function verifyChainIntegrity(): array
    {
        $audits = self::orderBy('created_at', 'asc')->orderBy('id', 'asc')->get();

        $results = [
            'total_audits' => $audits->count(),
            'verified_audits' => 0,
            'failed_audits' => 0,
            'broken_links' => [],
            'invalid_hashes' => [],
        ];

        $previousHash = null;

        foreach ($audits as $audit) {
            // Verify that the previous hash matches
            if ($audit->previous_signature_hash !== $previousHash) {
                $results['broken_links'][] = [
                    'audit_id' => $audit->id,
                    'draw_id' => $audit->draw_id,
                    'expected_previous_hash' => $previousHash,
                    'actual_previous_hash' => $audit->previous_signature_hash,
                ];
                $results['failed_audits']++;
            } else {
                // Verify the signature hash itself
                $expectedHash = self::generateSignature(
                    $audit->competition_id,
                    $audit->draw_id,
                    $audit->drawn_at_utc->format('Y-m-d H:i:s'),
                    $audit->total_entries,
                    $audit->rng_seed_or_hash,
                    $audit->selected_entry_id,
                    $previousHash
                );

                if ($expectedHash !== $audit->signature_hash) {
                    $results['invalid_hashes'][] = [
                        'audit_id' => $audit->id,
                        'draw_id' => $audit->draw_id,
                        'expected_hash' => $expectedHash,
                        'actual_hash' => $audit->signature_hash,
                    ];
                    $results['failed_audits']++;
                } else {
                    $results['verified_audits']++;
                }
            }

            $previousHash = $audit->signature_hash;
        }

        $results['is_valid'] = $results['failed_audits'] === 0;

        return $results;
    }

    /**
     * Generate a hash of eligible ticket IDs for the draw pool.
     *
     * @param  \Illuminate\Support\Collection  $tickets
     */
    public static function generatePoolHash($tickets): string
    {
        $ticketIds = $tickets->pluck('id')->sort()->values()->toArray();

        return hash('sha256', implode(',', $ticketIds));
    }
}
