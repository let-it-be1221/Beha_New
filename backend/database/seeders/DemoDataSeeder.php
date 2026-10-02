<?php

namespace Database\Seeders;

use App\Enums\ApplicantStatus;
use App\Enums\CustomerStatus;
use App\Enums\PropertyStatus;
use App\Models\Applicant;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Generation;
use App\Models\Property;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DemoDataSeeder — creates demo users for every role + sample data so
 * each role's dashboard renders non-empty stats on first run.
 *
 * Run via: php artisan migrate:fresh --seed
 *
 * Demo credentials are printed to console.
 *
 * NOTE: This seeder is for LOCAL DEV ONLY. Disable in production via
 * `seeders.demo_data` env flag if needed.
 */
class DemoDataSeeder extends Seeder
{
    private array $demoCredentials = [];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  Seeding demo data for each role…');
        $this->command->info('═══════════════════════════════════════════════════════');

        // 1. Create org hierarchy: 1 generation → 1 branch → 1 team
        $gen = Generation::firstOrCreate(
            ['generation_number' => 1],
            ['name' => 'Generation 1', 'is_active' => true],
        );
        $branch = Branch::firstOrCreate(
            ['generation_id' => $gen->id, 'branch_number' => 1],
            ['name' => 'Branch 1', 'is_active' => true, 'generation_id' => $gen->id],
        );
        $team = Team::firstOrCreate(
            ['branch_id' => $branch->id, 'team_number' => 1],
            ['name' => 'Team 1', 'is_active' => true, 'branch_id' => $branch->id],
        );

        // 2. Create one user per role
        $this->createRoleUser('executive_officer', 'executive.officer', 'executive@beha.local', 'Executive', 'Officer', 0);
        $this->createRoleUser('record_officer',    'record.officer',  'record@beha.local',    'Record',    'Officer', 0);
        $this->createRoleUser('finance_officer',   'finance.officer', 'finance@beha.local',   'Finance',   'Officer', 0);

        $genLeader = $this->createRoleUser('generation_leader', 'gen.leader', 'genleader@beha.local', 'Gen', 'Leader', 5);
        $gen->update(['leader_user_id' => $genLeader->id]);

        $branchLeader = $this->createRoleUser('branch_leader', 'branch.leader', 'branchleader@beha.local', 'Branch', 'Leader', 4);
        $branch->update(['leader_user_id' => $branchLeader->id]);

        $teamLeader = $this->createRoleUser('team_leader', 'team.leader', 'teamleader@beha.local', 'Team', 'Leader', 3);
        $team->update(['leader_user_id' => $teamLeader->id]);

        $teamMember = $this->createRoleUser('team_member', 'team.member', 'teammember@beha.local', 'Team', 'Member', 1);

        // 3. Assign team members to the team
        foreach ([$genLeader, $branchLeader, $teamLeader, $teamMember] as $user) {
            TeamMember::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'team_id'   => $team->id,
                    'level'     => $user->level,
                    'joined_at' => now()->subDays(30),
                    'is_active' => true,
                ],
            );
            $user->update(['current_team_id' => $team->id]);
        }

        // 4. Sample customers (various statuses)
        $this->seedCustomers($teamMember, $team, $branch, $gen);

        // 5. Sample properties (various statuses)
        $this->seedProperties($genLeader, $gen);

        // 6. Sample applicants (various stages)
        $this->seedApplicants();

        // 7. Print credentials
        $this->printCredentials();
    }

    private function createRoleUser(string $role, string $username, string $email, string $firstName, string $lastName, int $level): User
    {
        $tempPassword = 'Demo1234!';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'official_id'  => $this->generateOfficialId(),
                'username'      => $username,
                'password'      => $tempPassword,
                'is_active'     => true,
                'level'         => $level,
                'must_change_password' => false, // demo users don't have to change password
            ],
        );
        $user->assignRole($role);
        $user->syncRoles([$role]);

        $this->demoCredentials[] = [
            'role'     => $role,
            'username' => $username,
            'email'    => $email,
            'password' => $tempPassword,
            'level'    => $level,
        ];

        return $user;
    }

    private function generateOfficialId(): string
    {
        $seq = \App\Models\IdSequence::lockForUpdate()->where('sequence_key', 'official_id')->firstOrFail();
        $value = $seq->next_value;
        $seq->next_value = $value + 1;
        $seq->last_used_at = now();
        $seq->save();
        return $seq->prefix . str_pad((string) $value, $seq->padding, '0', STR_PAD_LEFT);
    }

    private function seedCustomers(User $agent, Team $team, Branch $branch, Generation $gen): void
    {
        $statuses = [
            CustomerStatus::Draft,
            CustomerStatus::PendingTeamEvaluation,
            CustomerStatus::ApprovedByTeam,
            CustomerStatus::PendingRecordApproval,
            CustomerStatus::Registered,
        ];
        $faker = \Faker\Factory::create();
        $year = now()->year;
        $seq = 0;

        foreach ($statuses as $status) {
            $seq++;
            $refCode = "CUS-{$year}-" . str_pad((string) (1000 + $seq), 6, '0', STR_PAD_LEFT);
            Customer::firstOrCreate(
                ['reference_code' => $refCode],
                [
                    'full_name'             => $faker->name(),
                    'email'                 => $faker->unique()->safeEmail(),
                    'phone'                 => $faker->phoneNumber(),
                    'national_id'           => 'ID-' . $faker->unique()->randomNumber(7, true),
                    'customer_source'        => $faker->randomElement(['referral', 'walk-in', 'social media']),
                    'sales_agent_user_id'   => $agent->id,
                    'team_id'               => $team->id,
                    'branch_id'             => $branch->id,
                    'generation_id'         => $gen->id,
                    'status'                => $status,
                ],
            );
        }
    }

    private function seedProperties(User $registeredBy, Generation $gen): void
    {
        $properties = [
            ['name' => 'Sunset Apartments',     'type' => 'apartment',  'city' => 'Addis Ababa', 'price' => 2500000, 'status' => PropertyStatus::PendingVerification],
            ['name' => 'Bole Garden Villas',     'type' => 'villa',       'city' => 'Addis Ababa', 'price' => 8500000, 'status' => PropertyStatus::Verified],
            ['name' => 'Piazza Heights',         'type' => 'apartment',  'city' => 'Addis Ababa', 'price' => 1800000, 'status' => PropertyStatus::PendingAssetCoding],
            ['name' => 'Lake View Condos',       'type' => 'condo',       'city' => 'Bahir Dar',    'price' => 3200000, 'status' => PropertyStatus::Published],
            ['name' => 'Mountain Ridge Houses',  'type' => 'house',       'city' => 'Gondar',        'price' => 5500000, 'status' => PropertyStatus::Published],
            ['name' => 'Commercial Tower CBD',    'type' => 'commercial', 'city' => 'Addis Ababa', 'price' => 25000000,'status' => PropertyStatus::Published],
        ];

        $year = now()->year;
        $seq = 0;
        foreach ($properties as $p) {
            $seq++;
            $assetCode = in_array($p['status'], [PropertyStatus::PendingAssetCoding, PropertyStatus::Published], true)
                ? "AST-{$year}-" . str_pad((string) (2000 + $seq), 6, '0', STR_PAD_LEFT)
                : null;

            Property::firstOrCreate(
                ['name' => $p['name']],
                [
                    'asset_code'              => $assetCode,
                    'property_type'           => $p['type'],
                    'city'                    => $p['city'],
                    'country'                 => 'Ethiopia',
                    'region'                  => $p['city'] === 'Addis Ababa' ? 'Addis Ababa' : 'Amhara',
                    'address'                 => $p['city'] . ' Main St',
                    'price'                   => $p['price'],
                    'bedrooms'                => rand(1, 5),
                    'bathrooms'               => rand(1, 3),
                    'size_sqm'                => rand(60, 300),
                    'developer'               => 'Beha Developers',
                    'ownership_type'           => 'freehold',
                    'status'                   => $p['status'],
                    'is_published'             => $p['status'] === PropertyStatus::Published,
                    'published_at'             => $p['status'] === PropertyStatus::Published ? now() : null,
                    'registered_by_user_id'    => $registeredBy->id,
                    'generation_id'            => $gen->id,
                    'description'              => 'Beautiful property in ' . $p['city'] . '.',
                ],
            );
        }
    }

    private function seedApplicants(): void
    {
        $faker = \Faker\Factory::create();
        $statuses = [
            ApplicantStatus::Submitted,
            ApplicantStatus::TeamLeaderScreening,
            ApplicantStatus::BranchAssignment,
            ApplicantStatus::RecordVerification,
        ];
        $seq = 0;
        $year = now()->year;

        foreach ($statuses as $status) {
            $seq++;
            $code = "APP-{$year}-" . str_pad((string) (3000 + $seq), 6, '0', STR_PAD_LEFT);
            Applicant::firstOrCreate(
                ['application_code' => $code],
                [
                    'full_name'         => $faker->name(),
                    'email'             => $faker->unique()->safeEmail(),
                    'phone'             => $faker->phoneNumber(),
                    'national_id'       => 'ID-' . $faker->unique()->randomNumber(7, true),
                    'address'           => $faker->address(),
                    'status'            => $status,
                    'terms_accepted_at' => now()->subDays(rand(1, 10)),
                ],
            );
        }
    }

    private function printCredentials(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  DEMO LOGIN CREDENTIALS (password: Demo1234!)');
        $this->command->info('  → Login at http://localhost:5173/login');
        $this->command->info('═══════════════════════════════════════════════════════');
        foreach ($this->demoCredentials as $c) {
            $levelLabel = $c['level'] > 0 ? " (Level {$c['level']})" : '';
            $this->command->info(sprintf(
                '  %-22s %-25s %s%s',
                "[{$c['role']}]",
                $c['email'],
                $c['password'],
                $levelLabel,
            ));
        }
        $this->command->info('');
        $this->command->info('  Plus the default System Administrator from AdminSeeder:');
        $this->command->info('    admin@beha.local  /  (printed by AdminSeeder)');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('');
    }
}
