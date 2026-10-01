<?php

namespace Database\Seeders;

use App\Models\UserLevel;
use Illuminate\Database\Seeder;

/**
 * Seeds the 5 vertical user levels (spec §3).
 */
class UserLevelsSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [1, 'Team Member',         'Entry-level sales team member'],
            [2, 'Senior Team Member',  'Promoted team member with proven performance'],
            [3, 'Team Leader',         'Leads a team of up to 10 team members'],
            [4, 'Branch Leader',       'Leads a branch of up to 10 teams'],
            [5, 'Generation Leader',   'Leads a generation of up to 10 branches; can register properties'],
        ];

        foreach ($levels as [$code, $name, $desc]) {
            UserLevel::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'description' => $desc, 'is_active' => true],
            );
        }

        $this->command->info('User levels seeded: ' . count($levels) . ' entries.');
    }
}
