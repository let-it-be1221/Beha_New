<?php

namespace App\Services\ID;

use Illuminate\Support\Facades\DB;

/**
 * Confidential ID Generator — spec §6.
 *
 * Format (default): {yy}-{GG}-{BB}-{TT}-{RR}
 *   yy = 2-digit registration year
 *   GG = generation number, 2-padded
 *   BB = branch number, 2-padded
 *   TT = team number, 2-padded
 *   RR = performance rank within team, 2-padded
 *
 * Format is fully configurable via CONFIDENTIAL_ID_FORMAT env.
 * The value is FROZEN at the moment of generation — it is NOT regenerated
 * when the user later moves teams.
 */
class ConfidentialIdGenerator
{
    public function __construct(
        private IdHistoryRecorder $history,
    ) {}

    /**
     * Format a confidential ID for an applicant at assignment time.
     *
     * @param array{generation:int,branch:int,team:int,rank:int} $parts
     */
    public function format(int $year, array $parts): string
    {
        $template = (string) config('beha.confidential_id.format', '{yy}-{GG}-{BB}-{TT}-{RR}');
        $pad      = (int) config('beha.confidential_id.pad_length', 2);

        $replacements = [
            '{yy}'   => str_pad((string) ($year % 100), 2, '0', STR_PAD_LEFT),
            '{yyyy}' => (string) $year,
            '{GG}'   => str_pad((string) $parts['generation'], $pad, '0', STR_PAD_LEFT),
            '{BB}'   => str_pad((string) $parts['branch'], $pad, '0', STR_PAD_LEFT),
            '{TT}'   => str_pad((string) $parts['team'], $pad, '0', STR_PAD_LEFT),
            '{RR}'   => str_pad((string) $parts['rank'], $pad, '0', STR_PAD_LEFT),
        ];

        return strtr($template, $replacements);
    }

    public function forApplicant(int $applicantId, int $year, array $parts): string
    {
        $value = $this->format($year, $parts);

        DB::transaction(function () use ($applicantId, $value, $parts, $year) {
            $this->history->record(
                sequenceKey: 'confidential_id',
                generatedValue: $value,
                subjectType: 'App\\Models\\Applicant',
                subjectId: $applicantId,
                payload: [
                    'year'        => $year,
                    'generation'  => $parts['generation'],
                    'branch'      => $parts['branch'],
                    'team'        => $parts['team'],
                    'rank'        => $parts['rank'],
                    'context'     => 'applicant_onboarding',
                ],
            );
        });

        return $value;
    }
}
