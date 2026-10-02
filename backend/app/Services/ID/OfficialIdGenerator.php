<?php

namespace App\Services\ID;

use Illuminate\Support\Facades\DB;

/**
 * Official ID Generator — BH000001 (spec §5.1).
 *
 * Format: prefix + zero-padded sequential number (configurable).
 * Immutable, never reused, sequential.
 */
class OfficialIdGenerator
{
    public const SEQUENCE_KEY = 'official_id';

    public function __construct(
        private IdSequenceService $sequences,
        private IdHistoryRecorder $history,
    ) {}

    public function next(): string
    {
        $prefix   = (string) config('beha.official_id.prefix', 'BH');
        $padding  = (int) config('beha.official_id.pad_length', 6);

        return DB::transaction(function () use ($prefix, $padding) {
            $this->sequences->ensureExists(self::SEQUENCE_KEY, $prefix, $padding);
            $value = $this->sequences->next(self::SEQUENCE_KEY);
            $formatted = $prefix . str_pad((string) $value, $padding, '0', STR_PAD_LEFT);

            $this->history->record(
                sequenceKey: self::SEQUENCE_KEY,
                generatedValue: $formatted,
                subjectType: 'pending_user',
                subjectId: 0,
                payload: ['raw_value' => $value],
            );

            return $formatted;
        });
    }

    /**
     * Same as next() but binds the history record to the actual applicant.
     * Used when ID generation happens inside the applicant onboarding workflow.
     */
    public function forApplicant(int $applicantId): string
    {
        $prefix   = (string) config('beha.official_id.prefix', 'BH');
        $padding  = (int) config('beha.official_id.pad_length', 6);

        $this->sequences->ensureExists(self::SEQUENCE_KEY, $prefix, $padding);

        return DB::transaction(function () use ($prefix, $padding, $applicantId) {
            $value = $this->sequences->next(self::SEQUENCE_KEY);
            $formatted = $prefix . str_pad((string) $value, $padding, '0', STR_PAD_LEFT);

            $this->history->record(
                sequenceKey: self::SEQUENCE_KEY,
                generatedValue: $formatted,
                subjectType: 'App\\Models\\Applicant',
                subjectId: $applicantId,
                payload: ['raw_value' => $value, 'context' => 'applicant_onboarding'],
            );

            return $formatted;
        });
    }
}
