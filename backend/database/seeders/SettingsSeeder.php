<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * SettingsSeeder — populates system_settings with the configurable
 * organization-level business rules (spec §35).
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Org identity
            ['key' => 'org.name', 'value' => env('ORG_NAME', 'Beha Real Estate'), 'type' => 'string', 'description' => 'Organization display name'],
            ['key' => 'org.logo_path', 'value' => 'images/logo.png', 'type' => 'string', 'description' => 'Relative path in storage/app/public'],

            // ID formats
            ['key' => 'ids.official_prefix', 'value' => 'BH', 'type' => 'string', 'description' => 'Official ID prefix (spec §5.1)'],
            ['key' => 'ids.official_pad_length', 'value' => '6', 'type' => 'int', 'description' => 'Zero-pad length for Official ID'],
            ['key' => 'ids.customer_ref_prefix', 'value' => 'CUS', 'type' => 'string', 'description' => 'Customer reference code prefix'],
            ['key' => 'ids.asset_code_prefix', 'value' => 'AST', 'type' => 'string', 'description' => 'Property asset code prefix'],
            ['key' => 'ids.confidential_format', 'value' => '{yy}-{GG}-{BB}-{TT}-{RR}', 'type' => 'string', 'description' => 'Confidential ID format template'],

            // Capacity (spec §16)
            ['key' => 'capacity.team_members_per_team', 'value' => '10', 'type' => 'int', 'description' => 'Max team members per team'],
            ['key' => 'capacity.teams_per_branch', 'value' => '10', 'type' => 'int', 'description' => 'Max teams per branch'],
            ['key' => 'capacity.branches_per_generation', 'value' => '10', 'type' => 'int', 'description' => 'Max branches per generation'],

            // Security (spec §25)
            ['key' => 'security.password_min_length', 'value' => '12', 'type' => 'int', 'description' => 'Minimum password length'],
            ['key' => 'security.password_mixed_case', 'value' => '1', 'type' => 'bool', 'description' => 'Require upper + lower case'],
            ['key' => 'security.password_require_number', 'value' => '1', 'type' => 'bool', 'description' => 'Require at least one digit'],
            ['key' => 'security.password_require_symbol', 'value' => '1', 'type' => 'bool', 'description' => 'Require at least one symbol'],
            ['key' => 'security.temp_password_ttl_hours', 'value' => '24', 'type' => 'int', 'description' => 'Temporary password TTL in hours'],
            ['key' => 'security.login_max_attempts', 'value' => '5', 'type' => 'int', 'description' => 'Max failed login attempts before lockout'],
            ['key' => 'security.login_decay_minutes', 'value' => '15', 'type' => 'int', 'description' => 'Lockout duration in minutes'],

            // Audit (spec §24)
            ['key' => 'audit.retention_days', 'value' => '365', 'type' => 'int', 'description' => 'Audit log retention period in days'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }

        $this->command->info('System settings seeded: ' . count($settings) . ' entries.');
    }
}
