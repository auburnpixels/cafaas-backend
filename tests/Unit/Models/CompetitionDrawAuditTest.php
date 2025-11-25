<?php

namespace Tests\Unit\Models;

use App\Models\CompetitionDrawAudit;
use Tests\TestCase;

class CompetitionDrawAuditTest extends TestCase
{
    public function test_generates_consistent_signature_hash(): void
    {
        $competitionId = 123;
        $drawId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $drawnAtUtc = '2025-11-04 12:00:00';
        $totalEntries = 500;
        $selectedEntryId = 42;
        $rngSeedOrHash = 'abc123def456';

        $signature1 = CompetitionDrawAudit::generateSignature(
            $competitionId,
            $drawId,
            $drawnAtUtc,
            $totalEntries,
            $selectedEntryId,
            $rngSeedOrHash
        );

        $signature2 = CompetitionDrawAudit::generateSignature(
            $competitionId,
            $drawId,
            $drawnAtUtc,
            $totalEntries,
            $selectedEntryId,
            $rngSeedOrHash
        );

        // Same inputs should produce same signature
        $this->assertEquals($signature1, $signature2);
        $this->assertEquals(64, strlen($signature1)); // SHA256 produces 64 character hex
    }

    public function test_generates_different_signature_for_different_competition_id(): void
    {
        $drawId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $drawnAtUtc = '2025-11-04 12:00:00';
        $totalEntries = 500;
        $selectedEntryId = 42;
        $rngSeedOrHash = 'abc123def456';

        $signature1 = CompetitionDrawAudit::generateSignature(
            123,
            $drawId,
            $drawnAtUtc,
            $totalEntries,
            $selectedEntryId,
            $rngSeedOrHash
        );

        $signature2 = CompetitionDrawAudit::generateSignature(
            456,
            $drawId,
            $drawnAtUtc,
            $totalEntries,
            $selectedEntryId,
            $rngSeedOrHash
        );

        // Different competition IDs should produce different signatures
        $this->assertNotEquals($signature1, $signature2);
    }

    public function test_generates_different_signature_for_different_winning_ticket(): void
    {
        $competitionId = 123;
        $drawId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $drawnAtUtc = '2025-11-04 12:00:00';
        $totalEntries = 500;
        $rngSeedOrHash = 'abc123def456';

        $signature1 = CompetitionDrawAudit::generateSignature(
            $competitionId,
            $drawId,
            $drawnAtUtc,
            $totalEntries,
            42,
            $rngSeedOrHash
        );

        $signature2 = CompetitionDrawAudit::generateSignature(
            $competitionId,
            $drawId,
            $drawnAtUtc,
            $totalEntries,
            999,
            $rngSeedOrHash
        );

        // Different winning tickets should produce different signatures
        $this->assertNotEquals($signature1, $signature2);
    }

    public function test_generates_different_signature_for_different_timestamp(): void
    {
        $competitionId = 123;
        $drawId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $totalEntries = 500;
        $selectedEntryId = 42;
        $rngSeedOrHash = 'abc123def456';

        $signature1 = CompetitionDrawAudit::generateSignature(
            $competitionId,
            $drawId,
            '2025-11-04 12:00:00',
            $totalEntries,
            $selectedEntryId,
            $rngSeedOrHash
        );

        $signature2 = CompetitionDrawAudit::generateSignature(
            $competitionId,
            $drawId,
            '2025-11-04 13:00:00',
            $totalEntries,
            $selectedEntryId,
            $rngSeedOrHash
        );

        // Different timestamps should produce different signatures
        $this->assertNotEquals($signature1, $signature2);
    }

    public function test_signature_uses_sha256_format(): void
    {
        $signature = CompetitionDrawAudit::generateSignature(
            123,
            'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            '2025-11-04 12:00:00',
            500,
            42,
            'abc123def456'
        );

        // SHA256 produces exactly 64 hexadecimal characters
        $this->assertEquals(64, strlen($signature));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);
    }

    public function test_generates_pool_hash_from_ticket_collection(): void
    {
        $tickets = collect([
            (object) ['id' => 5],
            (object) ['id' => 2],
            (object) ['id' => 8],
            (object) ['id' => 1],
        ]);

        $poolHash = CompetitionDrawAudit::generatePoolHash($tickets);

        // Should be a valid SHA256 hash
        $this->assertEquals(64, strlen($poolHash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $poolHash);

        // Same tickets should produce same hash
        $poolHash2 = CompetitionDrawAudit::generatePoolHash($tickets);
        $this->assertEquals($poolHash, $poolHash2);
    }

    public function test_pool_hash_is_order_independent(): void
    {
        // Tickets in different order
        $tickets1 = collect([
            (object) ['id' => 5],
            (object) ['id' => 2],
            (object) ['id' => 8],
        ]);

        $tickets2 = collect([
            (object) ['id' => 8],
            (object) ['id' => 5],
            (object) ['id' => 2],
        ]);

        $poolHash1 = CompetitionDrawAudit::generatePoolHash($tickets1);
        $poolHash2 = CompetitionDrawAudit::generatePoolHash($tickets2);

        // Should produce same hash regardless of input order (sorted internally)
        $this->assertEquals($poolHash1, $poolHash2);
    }

    public function test_pool_hash_changes_with_different_tickets(): void
    {
        $tickets1 = collect([
            (object) ['id' => 1],
            (object) ['id' => 2],
            (object) ['id' => 3],
        ]);

        $tickets2 = collect([
            (object) ['id' => 1],
            (object) ['id' => 2],
            (object) ['id' => 4], // Different ticket
        ]);

        $poolHash1 = CompetitionDrawAudit::generatePoolHash($tickets1);
        $poolHash2 = CompetitionDrawAudit::generatePoolHash($tickets2);

        // Different ticket sets should produce different hashes
        $this->assertNotEquals($poolHash1, $poolHash2);
    }

    public function test_signature_verification_example(): void
    {
        // Real-world example of verifying a signature
        $competitionId = 100;
        $drawId = '550e8400-e29b-41d4-a716-446655440000';
        $drawnAtUtc = '2025-11-04 14:30:00';
        $totalEntries = 1000;
        $selectedEntryId = 555;
        $rngPoolHash = hash('sha256', '1,2,3,4,5,555,1000');

        // Generate signature
        $signature = CompetitionDrawAudit::generateSignature(
            $competitionId,
            $drawId,
            $drawnAtUtc,
            $totalEntries,
            $selectedEntryId,
            $rngPoolHash
        );

        // Verify by regenerating with same parameters
        $verificationSignature = CompetitionDrawAudit::generateSignature(
            $competitionId,
            $drawId,
            $drawnAtUtc,
            $totalEntries,
            $selectedEntryId,
            $rngPoolHash
        );

        $this->assertEquals($signature, $verificationSignature);
    }
}
