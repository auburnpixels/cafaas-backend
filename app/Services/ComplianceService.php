<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Competition;
use App\Models\CompetitionDrawAudit;
use App\Models\Complaint;
use App\Models\Operator;
use App\Models\Ticket;

/**
 * Service for calculating compliance metrics and summaries
 */
final class ComplianceService
{
    /**
     * Get competition compliance summary for an operator
     *
     * @return array<string, mixed>
     */
    public function getCompetitionCompliance(Operator $operator): array
    {
        $competitions = Competition::where('operator_id', $operator->id)->get();

        $totalCompetitions = $competitions->count();
        $activeCompetitions = $competitions->where('status', 'active')->count();
        $endedCompetitions = $competitions->where('status', 'ended')->count();

        // Calculate competitions with draws
        $competitionsWithDraws = 0;
        $totalDraws = 0;
        $totalEntries = 0;

        foreach ($competitions as $competition) {
            $drawCount = $competition->drawAudits()->count();
            if ($drawCount > 0) {
                $competitionsWithDraws++;
            }
            $totalDraws += $drawCount;
            $totalEntries += $competition->tickets()->count();
        }

        // Calculate compliance score
        $complianceScore = $this->calculateComplianceScore($competitions);

        return [
            'total_competitions' => $totalCompetitions,
            'active_competitions' => $activeCompetitions,
            'ended_competitions' => $endedCompetitions,
            'competitions_with_draws' => $competitionsWithDraws,
            'total_draws' => $totalDraws,
            'total_entries' => $totalEntries,
            'compliance_score' => $complianceScore,
            'status' => $this->getComplianceStatus($complianceScore),
        ];
    }

    /**
     * Calculate compliance score based on various factors
     */
    private function calculateComplianceScore(\Illuminate\Support\Collection $competitions): float
    {
        if ($competitions->isEmpty()) {
            return 100.0; // Perfect score if no competitions yet
        }

        $totalScore = 0;
        $scorableCompetitions = 0;

        foreach ($competitions as $competition) {
            // Skip active competitions (not yet scorable)
            if ($competition->status === 'active') {
                continue;
            }

            $scorableCompetitions++;
            $competitionScore = 100.0;

            // Check if draw was completed
            $hasDrawAudit = $competition->drawAudits()->count() > 0;
            if (! $hasDrawAudit) {
                $competitionScore -= 30; // -30 points for missing draw
            }

            // Check if has entries
            $hasEntries = $competition->tickets()->count() > 0;
            if (! $hasEntries) {
                $competitionScore -= 20; // -20 points for no entries
            }

            // Check if proper audit trail exists
            if ($hasDrawAudit) {
                $auditCount = $competition->drawAudits()->count();
                if ($auditCount < 1) {
                    $competitionScore -= 15;
                }
            }

            $totalScore += max(0, $competitionScore);
        }

        // If no scorable competitions, return perfect score
        if ($scorableCompetitions === 0) {
            return 100.0;
        }

        return round($totalScore / $scorableCompetitions, 1);
    }

    /**
     * Get compliance status based on score
     */
    private function getComplianceStatus(float $score): string
    {
        if ($score >= 95) {
            return 'excellent';
        } elseif ($score >= 85) {
            return 'good';
        } elseif ($score >= 70) {
            return 'fair';
        } else {
            return 'needs_attention';
        }
    }

    /**
     * Get detailed compliance report for a specific competition
     * Uses lifecycle-aware logic: compliance depends on competition status
     *
     * @return array<string, mixed>
     */
    public function getCompetitionComplianceDetail(Competition $competition): array
    {
        $drawAudits = $competition->drawAudits()->count();
        $drawEvents = $competition->drawEvents()->count();
        $totalEntries = $competition->tickets()->count();

        $hasValidDraws = $drawAudits > 0;
        $hasAuditTrail = $drawEvents > 0;
        $hasEntries = $totalEntries > 0;

        $status = strtolower($competition->status);

        // Check if draw is overdue
        $isDrawOverdue = false;
        $daysOverdue = 0;
        if ($competition->draw_at && $competition->draw_at->isPast() && ! $hasValidDraws) {
            $isDrawOverdue = true;
            $daysOverdue = now()->diffInDays($competition->draw_at);
        }

        // Lifecycle-aware compliance logic
        $complianceStatus = 'good';
        $compliancePercentage = 100.0;
        $issues = [];

        if (in_array($status, ['pending', 'active'])) {
            // For active/pending: just check if basic setup is ok
            // No penalty for missing draws (they're not needed yet)
            $complianceStatus = 'good';
            $compliancePercentage = 100.0;

            // Warn if draw date is approaching and no entries
            if ($competition->draw_at && $competition->draw_at->isFuture()) {
                $daysUntilDraw = now()->diffInDays($competition->draw_at);
                if ($daysUntilDraw <= 7 && ! $hasEntries) {
                    $complianceStatus = 'fair';
                    $compliancePercentage = 85.0;
                    $issues[] = 'Draw date approaching with no entries';
                }
            }
        } elseif ($status === 'ended' && $isDrawOverdue) {
            // Critical: ended but draw is overdue
            $complianceStatus = 'needs_attention';
            $compliancePercentage = 40.0;
            $issues[] = "Draw overdue by {$daysOverdue} day(s)";

            if (! $hasEntries) {
                $issues[] = 'No entries to draw from';
            }
        } elseif (in_array($status, ['completed', 'drawn'])) {
            // Should have draws and audit trail
            if (! $hasValidDraws) {
                $complianceStatus = 'needs_attention';
                $compliancePercentage = 30.0;
                $issues[] = 'Missing draw audits';
            } elseif (! $hasAuditTrail) {
                $complianceStatus = 'fair';
                $compliancePercentage = 75.0;
                $issues[] = 'Incomplete audit trail';
            } else {
                $complianceStatus = 'excellent';
                $compliancePercentage = 100.0;
            }
        } elseif ($status === 'ended') {
            // Ended but not yet drawn - give grace period
            if ($competition->draw_at && $competition->draw_at->isFuture()) {
                // Draw date hasn't arrived yet
                $complianceStatus = 'good';
                $compliancePercentage = 100.0;
            } else {
                // Draw date passed, but within grace period
                $complianceStatus = 'fair';
                $compliancePercentage = 85.0;
            }
        }

        return [
            'competition_uuid' => $competition->id,
            'competition_name' => $competition->name,
            'status' => $competition->status,
            'total_entries' => $totalEntries,
            'total_draws' => $drawAudits,
            'audit_events' => $drawEvents,
            'compliance_checks' => [
                'has_draws' => $hasValidDraws,
                'has_audit_trail' => $hasAuditTrail,
                'has_entries' => $hasEntries,
            ],
            'compliance_percentage' => $compliancePercentage,
            'compliance_status' => $complianceStatus,
            'is_draw_overdue' => $isDrawOverdue,
            'days_overdue' => $daysOverdue,
            'issues' => $issues,
        ];
    }

    /**
     * Get operator compliance (alias for getCompetitionCompliance)
     *
     * @return array<string, mixed>
     */
    public function getOperatorCompliance(Operator $operator): array
    {
        return $this->getCompetitionCompliance($operator);
    }

    /**
     * Generate regulator dashboard with platform-wide compliance metrics
     *
     * @return array<string, mixed>
     */
    public function generateRegulatorDashboard(): array
    {
        $totalOperators = Operator::count();
        $activeOperators = Operator::where('is_active', true)->count();

        $totalCompetitions = Competition::count();
        $activeCompetitions = Competition::where('status', 'active')->count();
        $endedCompetitions = Competition::where('status', 'ended')->count();

        $totalDrawAudits = CompetitionDrawAudit::count();
        $totalDrawEvents = \App\Models\DrawEvent::count();

        // Calculate average compliance across all operators
        $operators = Operator::all();
        $complianceScores = [];

        foreach ($operators as $operator) {
            $compliance = $this->getCompetitionCompliance($operator);
            $complianceScores[] = $compliance['compliance_score'];
        }

        $averageCompliance = count($complianceScores) > 0
            ? round(array_sum($complianceScores) / count($complianceScores), 1)
            : 100.0;

        return [
            'stats' => [
                'total_operators' => $totalOperators,
                'active_operators' => $activeOperators,
                'total_competitions' => $totalCompetitions,
                'active_competitions' => $activeCompetitions,
                'ended_competitions' => $endedCompetitions,
                'total_draw_audits' => $totalDrawAudits,
                'total_draw_events' => $totalDrawEvents,
            ],
            'compliance' => [
                'average_score' => $averageCompliance,
                'status' => $this->getComplianceStatus($averageCompliance),
            ],
            'user' => request()->user(),
        ];
    }

    /**
     * Generate detailed compliance dashboard for regulators with per-raffle metrics
     *
     * @return array<string, mixed>
     */
    public function generateRegulatorComplianceDashboard(): array
    {
        // Get all competitions with necessary relationships
        $competitions = Competition::with(['operator', 'drawAudits', 'tickets', 'complaints'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRaffles = $competitions->count();
        $rafflesWithFreeEntry = 0;
        $rafflesWithAudits = 0;
        $totalActiveComplaints = 0;
        $totalPostalEntries = 0;

        $raffleDetails = [];

        foreach ($competitions as $competition) {
            // Calculate metrics for this competition
            $totalEntries = $competition->tickets()->count();
            $postalEntries = $competition->tickets()->where('free', true)->count();
            $freeEntryPercentage = $totalEntries > 0
                ? round(($postalEntries / $totalEntries) * 100, 2)
                : 0;

            $hasAudit = $competition->drawAudits()->count() > 0;
            $activeComplaints = $competition->complaints()
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count();

            // Calculate compliance score
            $complianceScore = $this->calculateRaffleComplianceScore(
                $postalEntries > 0,
                $hasAudit,
                $activeComplaints
            );

            // Track overall stats
            if ($postalEntries > 0) {
                $rafflesWithFreeEntry++;
            }
            if ($hasAudit) {
                $rafflesWithAudits++;
            }
            $totalActiveComplaints += $activeComplaints;
            $totalPostalEntries += $postalEntries;

            // Prepare raffle detail
            $raffleDetails[] = [
                'raffle_id' => $competition->id,
                'raffle_id_short' => substr($competition->id, 0, 8),
                'external_id' => $competition->external_id,
                'name' => $competition->name ?? $competition->name ?? 'Unnamed',
                'status' => $competition->status,
                'total_entries' => $totalEntries,
                'postal_entries' => $postalEntries,
                'free_entry_percentage' => $freeEntryPercentage,
                'has_audit' => $hasAudit,
                'audit_count' => $competition->drawAudits()->count(),
                'active_complaints' => $activeComplaints,
                'compliance_score' => $complianceScore,
            ];
        }

        // Calculate top-level statistics
        $freeEntryPercentage = $totalRaffles > 0
            ? round(($rafflesWithFreeEntry / $totalRaffles) * 100, 2)
            : 100;

        $auditLogsPercentage = $totalRaffles > 0
            ? round(($rafflesWithAudits / $totalRaffles) * 100, 2)
            : 0;

        $avgPostalPerRaffle = $totalRaffles > 0
            ? round($totalPostalEntries / $totalRaffles, 2)
            : 0;

        return [
            'summary' => [
                'raffles_hosted' => $totalRaffles,
                'with_free_entry_route' => $rafflesWithFreeEntry,
                'with_free_entry_percentage' => $freeEntryPercentage,
                'with_audit_logs' => $rafflesWithAudits,
                'with_audit_logs_percentage' => $auditLogsPercentage,
                'active_complaints' => $totalActiveComplaints,
                'postal_entries_received' => $totalPostalEntries,
                'avg_postal_per_raffle' => $avgPostalPerRaffle,
            ],
            'raffles' => $raffleDetails,
            'user' => request()->user(),
        ];
    }

    /**
     * Generate detailed compliance dashboard for a specific operator with per-raffle metrics
     *
     * @return array<string, mixed>
     */
    public function generateOperatorComplianceDashboard(Operator $operator): array
    {
        // Get competitions for this operator with necessary relationships
        $competitions = Competition::where('operator_id', $operator->id)
            ->with(['drawAudits', 'tickets', 'complaints'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRaffles = $competitions->count();
        $rafflesWithFreeEntry = 0;
        $rafflesWithAudits = 0;
        $totalActiveComplaints = 0;
        $totalPostalEntries = 0;

        $raffleDetails = [];

        foreach ($competitions as $competition) {
            // Calculate metrics for this competition
            $totalEntries = $competition->tickets()->count();
            $postalEntries = $competition->tickets()->where('free', true)->count();
            $freeEntryPercentage = $totalEntries > 0
                ? round(($postalEntries / $totalEntries) * 100, 2)
                : 0;

            $hasAudit = $competition->drawAudits()->count() > 0;
            $activeComplaints = $competition->complaints()
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count();

            // Calculate compliance score
            $complianceScore = $this->calculateRaffleComplianceScore(
                $postalEntries > 0,
                $hasAudit,
                $activeComplaints
            );

            // Track overall stats
            if ($postalEntries > 0) {
                $rafflesWithFreeEntry++;
            }
            if ($hasAudit) {
                $rafflesWithAudits++;
            }
            $totalActiveComplaints += $activeComplaints;
            $totalPostalEntries += $postalEntries;

            // Prepare raffle detail
            $raffleDetails[] = [
                'raffle_id' => $competition->id,
                'raffle_id_short' => substr($competition->id, 0, 8),
                'external_id' => $competition->external_id,
                'name' => $competition->name ?? $competition->name ?? 'Unnamed',
                'status' => $competition->status,
                'total_entries' => $totalEntries,
                'postal_entries' => $postalEntries,
                'free_entry_percentage' => $freeEntryPercentage,
                'has_audit' => $hasAudit,
                'audit_count' => $competition->drawAudits()->count(),
                'active_complaints' => $activeComplaints,
                'compliance_score' => $complianceScore,
            ];
        }

        // Calculate top-level statistics
        $freeEntryPercentage = $totalRaffles > 0
            ? round(($rafflesWithFreeEntry / $totalRaffles) * 100, 2)
            : 100;

        $auditLogsPercentage = $totalRaffles > 0
            ? round(($rafflesWithAudits / $totalRaffles) * 100, 2)
            : 0;

        $avgPostalPerRaffle = $totalRaffles > 0
            ? round($totalPostalEntries / $totalRaffles, 2)
            : 0;

        return [
            'summary' => [
                'raffles_hosted' => $totalRaffles,
                'with_free_entry_route' => $rafflesWithFreeEntry,
                'with_free_entry_percentage' => $freeEntryPercentage,
                'with_audit_logs' => $rafflesWithAudits,
                'with_audit_logs_percentage' => $auditLogsPercentage,
                'active_complaints' => $totalActiveComplaints,
                'postal_entries_received' => $totalPostalEntries,
                'avg_postal_per_raffle' => $avgPostalPerRaffle,
            ],
            'raffles' => $raffleDetails,
            'operator' => [
                'id' => $operator->id,
                'name' => $operator->name,
                'uuid' => $operator->id,
            ],
            'user' => request()->user(),
        ];
    }

    /**
     * Calculate compliance score for a specific raffle
     */
    private function calculateRaffleComplianceScore(
        bool $hasFreeEntry,
        bool $hasAudit,
        int $activeComplaints
    ): int {
        $score = 0;

        // Free entry route available: 40 points
        if ($hasFreeEntry) {
            $score += 40;
        }

        // Has audit logs: 40 points
        if ($hasAudit) {
            $score += 40;
        }

        // No active complaints: 20 points
        if ($activeComplaints === 0) {
            $score += 20;
        }

        return $score;
    }
}
