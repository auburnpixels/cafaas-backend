<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Complaint;
use App\Models\CompetitionDrawAudit;
use App\Models\DrawAudit;
use App\Models\DrawEvent;
use App\Models\Operator;
use App\Models\Prize;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CsvDataSeeder extends Seeder
{
    private string $operatorId;
    private int $userId;
    private array $competitionIdMap = [];
    private array $prizeIdMap = [];
    private array $ticketIdMap = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting CSV data seeding...');

        DB::transaction(function () {
            // Disable model events to prevent auto-hash generation
            Competition::unsetEventDispatcher();
            Ticket::unsetEventDispatcher();
            Prize::unsetEventDispatcher();
            DrawEvent::unsetEventDispatcher();
            CompetitionDrawAudit::unsetEventDispatcher();
            DrawAudit::unsetEventDispatcher();
            Complaint::unsetEventDispatcher();

            $this->seedOperatorAndUser();
            $this->seedCompetitions();
            $this->seedPrizes();
            $this->seedTickets();
            $this->seedDrawEvents();
            $this->seedCompetitionDrawAudits();
            $this->seedComplaints();
        });

        $this->command->info('CSV data seeding completed successfully!');
    }

    /**
     * Seed the Raffaly operator and admin user.
     */
    private function seedOperatorAndUser(): void
    {
        $this->command->info('Seeding Raffaly operator and admin user...');

        // Create Operator
        $operator = Operator::create([
            'id' => (string) Str::uuid(),
            'name' => 'Raffaly',
            'slug' => 'raffaly',
            'url' => 'https://raffaly.com',
            'is_active' => true,
        ]);

        $this->operatorId = $operator->id;
        $this->command->info("Created operator: {$operator->name} (ID: {$this->operatorId})");

        // Create Admin User
        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Raffaly Admin',
            'email' => 'liam@raffaly.com',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'operator_id' => $this->operatorId,
            'email_verified_at' => now(),
        ]);

        $this->userId = $user->id;
        $this->command->info("Created admin user: {$user->email} (ID: {$this->userId})");
    }

    /**
     * Seed competitions from CSV.
     */
    private function seedCompetitions(): void
    {
        $this->command->info('Seeding competitions from CSV...');

        $csvPath = resource_path('csv/competitions.csv');
        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file);

        $count = 0;
        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);

            $competitionUuid = $data['uuid'];
            $oldId = (int) $data['id'];

            // Map old statuses to new status system
            $oldStatus = $data['status'];
            $newStatus = $this->mapCompetitionStatus($oldStatus);

            Competition::insert([
                'id' => $competitionUuid,
                'operator_id' => $this->operatorId,
                'external_id' => (string) $oldId,
                'title' => $data['title'],
                'status' => $newStatus,
                'draw_at' => $data['draw_at'] ?: null,
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
                'ticket_quantity' => null,
            ]);

            // Store mapping: old_id => uuid
            $this->competitionIdMap[$oldId] = $competitionUuid;
            $count++;
        }

        fclose($file);
        $this->command->info("Seeded {$count} competitions");
    }

    /**
     * Map old competition statuses to new status system.
     */
    private function mapCompetitionStatus(string $oldStatus): string
    {
        return match ($oldStatus) {
            'pending', 'pending_incomplete', 'review', 'rejected' => 'unpublished',
            'active' => 'active',
            'ended', 'closed', 'awaiting_acceptance', 'awaiting_shipment' => 'awaiting_draw',
            'completed' => 'completed',
            default => 'unpublished',
        };
    }

    /**
     * Seed prizes from CSV.
     */
    private function seedPrizes(): void
    {
        $this->command->info('Seeding prizes from CSV...');

        $csvPath = resource_path('csv/prizes.csv');
        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file);

        $count = 0;
        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);

            $oldPrizeId = (int) $data['id'];
            $oldCompetitionId = (int) $data['competition_id'];
            $prizeUuid = (string) Str::uuid();

            // Lookup competition UUID from old ID
            $competitionUuid = $this->competitionIdMap[$oldCompetitionId] ?? null;

            if (!$competitionUuid) {
                $this->command->warn("Skipping prize {$oldPrizeId}: competition {$oldCompetitionId} not found");
                continue;
            }

            Prize::insert([
                'id' => $prizeUuid,
                'competition_id' => $competitionUuid,
                'external_id' => (string) $oldPrizeId,
                'title' => $data['name'] ?? 'Prize',
                'draw_order' => (int) ($data['order'] ?? 1),
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]);

            // Store mapping: old_prize_id => uuid
            $this->prizeIdMap[$oldPrizeId] = $prizeUuid;
            $count++;
        }

        fclose($file);
        $this->command->info("Seeded {$count} prizes");
    }

    /**
     * Seed tickets from CSV.
     */
    private function seedTickets(): void
    {
        $this->command->info('Seeding tickets from CSV...');

        $csvPath = resource_path('csv/tickets.csv');
        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file);

        $tickets = [];
        $count = 0;

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);

            $oldTicketId = (int) $data['id'];
            $oldCompetitionId = (int) $data['competition_id'];
            $ticketUuid = (string) Str::uuid();

            // Lookup competition UUID from old ID
            $competitionUuid = $this->competitionIdMap[$oldCompetitionId] ?? null;

            if (!$competitionUuid) {
                $this->command->warn("Skipping ticket {$oldTicketId}: competition {$oldCompetitionId} not found");
                continue;
            }

            // Parse ticket number and validate it's within PostgreSQL integer range
            $ticketNumber = (int) $data['number'];
            if ($ticketNumber > 2147483647 || $ticketNumber < -2147483648) {
                $this->command->warn("Skipping ticket {$oldTicketId}: number {$ticketNumber} exceeds integer range");
                continue;
            }

            $tickets[] = [
                'id' => $ticketUuid,
                'external_id' => (string) $oldTicketId,
                'competition_id' => $competitionUuid,
                'user_id' => null,
                'number' => $ticketNumber,
                'free' => (bool) ($data['free'] ?? false),
                'user_reference' => $data['name'] ?? null,
                'operator_id' => $this->operatorId,
                'question_answered_correctly' => true,
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ];

            // Store mapping: old_ticket_id => uuid
            $this->ticketIdMap[$oldTicketId] = $ticketUuid;
            $count++;

            // Batch insert every 500 tickets
            if (count($tickets) >= 500) {
                DB::table('tickets')->insert($tickets);
                $tickets = [];
            }
        }

        // Insert remaining tickets
        if (count($tickets) > 0) {
            DB::table('tickets')->insert($tickets);
        }

        fclose($file);
        $this->command->info("Seeded {$count} tickets");
    }

    /**
     * Seed draw events from CSV.
     */
    private function seedDrawEvents(): void
    {
        $this->command->info('Seeding draw events from CSV...');

        $csvPath = resource_path('csv/draw_events.csv');
        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file);

        $events = [];
        $count = 0;
        $skipped = 0;

        while (($row = fgetcsv($file)) !== false) {
            // Skip rows that don't have the same number of columns as headers
            if (count($row) !== count($headers)) {
                $this->command->warn('Skipping malformed CSV row');
                continue;
            }

            $data = array_combine($headers, $row);

            $oldCompetitionId = !empty($data['competition_id']) ? (int) $data['competition_id'] : null;

            // Lookup competition UUID from old ID
            $competitionUuid = null;
            if ($oldCompetitionId) {
                $competitionUuid = $this->competitionIdMap[$oldCompetitionId] ?? null;

                if (!$competitionUuid) {
                    // Skip events for competitions not in our CSV
                    $skipped++;
                    continue;
                }
            }

            $prizeId = null;

            // Parse event_payload JSON
            $eventPayload = $data['event_payload'] ?? '{}';
            if (!empty($eventPayload)) {
                $eventPayload = json_decode($eventPayload, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $eventPayload = [];
                }
            } else {
                $eventPayload = [];
            }

            $events[] = [
                'id' => $data['id'],
                'sequence' => (int) $data['sequence'],
                'competition_id' => $competitionUuid,
                'prize_id' => $prizeId,
                'operator_id' => $this->operatorId,
                'event_type' => $data['event_type'],
                'event_payload' => json_encode($eventPayload),
                'event_hash' => $data['event_hash'],
                'previous_event_hash' => $data['previous_event_hash'] ?: null,
                'actor_type' => $data['actor_type'] ?: null,
                'actor_id' => $data['actor_id'] ?: null,
                'ip_address' => $data['ip_address'] ?: null,
                'user_agent' => $data['user_agent'] ?: null,
                'created_at' => $data['created_at'],
            ];

            $count++;

            // Batch insert every 500 events
            if (count($events) >= 500) {
                DB::table('draw_events')->insert($events);
                $events = [];
            }
        }

        // Insert remaining events
        if (count($events) > 0) {
            DB::table('draw_events')->insert($events);
        }

        fclose($file);
        $this->command->info("Seeded {$count} draw events ({$skipped} skipped for missing competitions)");
    }

    /**
     * Seed competition draw audits from CSV.
     */
    private function seedCompetitionDrawAudits(): void
    {
        $this->command->info('Seeding competition draw audits from CSV...');

        $csvPath = resource_path('csv/competition_draw_audits.csv');
        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file);

        $count = 0;
        $sequenceCounter = 1; // Track sequence for draw audits
        
        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);

            $competitionUuid = $data['competition_uuid'];
            $oldPrizeId = (int) $data['prize_id'];
            $oldTicketId = (int) $data['selected_entry_id'];

            // Lookup prize UUID from old ID
            $prizeUuid = $this->prizeIdMap[$oldPrizeId] ?? null;

            // Lookup ticket UUID from old ID
            $ticketUuid = $this->ticketIdMap[$oldTicketId] ?? null;

            if (!$prizeUuid) {
                $this->command->warn("Skipping audit: prize {$oldPrizeId} not found");
                continue;
            }

            if (!$ticketUuid) {
                $this->command->warn("Skipping audit: ticket {$oldTicketId} not found");
                continue;
            }

            // Generate pool_hash from eligible tickets for this competition
            $eligibleTickets = DB::table('tickets')
                ->where('competition_id', $competitionUuid)
                ->where('question_answered_correctly', true)
                ->orderBy('id')
                ->pluck('id');

            $poolHash = hash('sha256', $eligibleTickets->implode(','));

            DrawAudit::insert([
                'id' => (string) Str::uuid(),
                'sequence' => $sequenceCounter++, // Set sequence incrementally
                'competition_id' => $competitionUuid,
                'prize_id' => $prizeUuid,
                'draw_id' => $data['draw_id'],
                'drawn_at_utc' => $data['drawn_at_utc'],
                'total_entries' => (int) $data['total_entries'],
                'rng_seed_or_hash' => $data['rng_seed_or_hash'],
                'pool_hash' => $poolHash,
                'selected_entry_id' => $ticketUuid,
                'signature_hash' => $data['signature_hash'],
                'previous_signature_hash' => $data['previous_signature_hash'] ?: null,
                'operator_id' => $this->operatorId,
                'created_at' => $data['created_at'],
                'updated_at' => now(),
            ]);

            $count++;
        }

        fclose($file);
        $this->command->info("Seeded {$count} competition draw audits");
    }

    /**
     * Seed complaints from CSV.
     */
    private function seedComplaints(): void
    {
        $this->command->info('Seeding complaints from CSV...');

        $csvPath = resource_path('csv/complaints.csv/complaints.csv');
        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file);

        $count = 0;
        $skipped = 0;

        while (($row = fgetcsv($file)) !== false) {
            // Skip rows that don't have the same number of columns as headers
            if (count($row) !== count($headers)) {
                $this->command->warn('Skipping malformed complaint CSV row');
                continue;
            }

            $data = array_combine($headers, $row);

            $oldCompetitionId = !empty($data['competition_id']) ? (int) $data['competition_id'] : null;

            // Lookup competition UUID from old ID
            $competitionUuid = null;
            if ($oldCompetitionId) {
                $competitionUuid = $this->competitionIdMap[$oldCompetitionId] ?? null;

                if (!$competitionUuid) {
                    // Skip complaints for competitions not in our CSV
                    $skipped++;
                    continue;
                }
            } else {
                // Skip complaints without competition_id
                $skipped++;
                continue;
            }

            Complaint::insert([
                'id' => (string) Str::uuid(),
                'competition_id' => $competitionUuid,
                'operator_id' => $this->operatorId,
                'user_id' => null, // Don't link to non-existent users
                'email' => $data['email'] ?? null,
                'name' => $data['name'] ?? null,
                'category' => $data['category'] ?? 'other',
                'message' => $data['message'] ?? '',
                'admin_notes' => $data['admin_notes'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'created_at' => $data['created_at'],
                'updated_at' => $data['updated_at'],
            ]);

            $count++;
        }

        fclose($file);
        $this->command->info("Seeded {$count} complaints ({$skipped} skipped for missing competitions)");
    }
}

