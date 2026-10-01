<?php

namespace Database\Seeders;

use App\Models\Generation;
use App\Models\IdSequence;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * AdminSeeder — creates the default System Administrator account
 * and ensures ID sequences exist.
 *
 * Spec §2.A, §5.1, §18.
 *
 * The temp password is printed to the console once.
 * On first login, the user must change it (ForcePasswordChange middleware).
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure ID sequences exist
        $this->ensureSequences();

        // 2. Create default generation if none exists
        $generation = Generation::firstOrCreate(
            ['generation_number' => 1],
            ['name' => 'Generation 1', 'is_active' => true],
        );

        // 3. Create the admin user (Official ID: BH000001)
        $adminEmail = 'admin@beha.local';
        if (!User::where('email', $adminEmail)->exists()) {
            $officialId = $this->generateOfficialId();
            $tempPassword = Str::random(24);

            $admin = User::create([
                'official_id'           => $officialId,
                'username'              => 'admin',
                'email'                 => $adminEmail,
                'password'              => $tempPassword, // hashed via cast
                'must_change_password' => true,
                'is_active'             => true,
                'level'                 => 0, // admin has no sales level
            ]);

            $admin->assignRole('system_administrator');

            $this->command->info('───────────────────────────────────────────────────');
            $this->command->info('Default System Administrator created:');
            $this->command->info("  Email:    {$adminEmail}");
            $this->command->info("  Username: admin");
            $this->command->info("  Official ID: {$officialId}");
            $this->command->info("  TEMP PASSWORD (shown once): {$tempPassword}");
            $this->command->info('  → You will be forced to change this on first login.');
            $this->command->info('───────────────────────────────────────────────────');
        } else {
            $this->command->info("Default admin already exists ({$adminEmail}).");
        }
    }

    private function ensureSequences(): void
    {
        IdSequence::firstOrCreate(
            ['sequence_key' => 'official_id'],
            ['prefix' => 'BH', 'padding' => 6, 'next_value' => 1],
        );
        $year = now()->year;
        IdSequence::firstOrCreate(
            ['sequence_key' => "cus:{$year}"],
            ['prefix' => 'CUS', 'padding' => 6, 'next_value' => 1],
        );
        IdSequence::firstOrCreate(
            ['sequence_key' => "ast:{$year}"],
            ['prefix' => 'AST', 'padding' => 6, 'next_value' => 1],
        );
    }

    private function generateOfficialId(): string
    {
        $seq = IdSequence::lockForUpdate()->where('sequence_key', 'official_id')->firstOrFail();
        $value = $seq->next_value;
        $seq->next_value++;
        $seq->last_used_at = now();
        $seq->save();

        return $seq->prefix . str_pad((string) $value, $seq->padding, '0', STR_PAD_LEFT);
    }
}
