<?php

namespace App\Services\ID;

use Illuminate\Support\Facades\DB;

/**
 * Customer Reference Code Generator — CUS-2026-000001 (spec §9).
 *
 * One sequence PER YEAR (key = 'cus:{year}'). Resets on Jan 1.
 * Never reused. If duplicate detected, the EXISTING customer's reference
 * code is kept — a new one is NOT issued.
 */
class CustomerReferenceGenerator
{
    public function __construct(
        private IdSequenceService $sequences,
        private IdHistoryRecorder $history,
    ) {}

    public function next(int $year, ?int $customerId = null, ?int $actorUserId = null): string
    {
        $key     = "cus:{$year}";
        $prefix  = (string) config('beha.customer_ref.prefix', 'CUS');
        $padding = (int) config('beha.customer_ref.pad_length', 6);

        $this->sequences->ensureExists($key, $prefix, $padding);

        return DB::transaction(function () use ($key, $prefix, $padding, $year, $customerId, $actorUserId) {
            $value = $this->sequences->next($key);
            $formatted = "{$prefix}-{$year}-" . str_pad((string) $value, $padding, '0', STR_PAD_LEFT);

            $this->history->record(
                sequenceKey: $key,
                generatedValue: $formatted,
                subjectType: 'App\\Models\\Customer',
                subjectId: $customerId ?? 0,
                payload: ['raw_value' => $value, 'year' => $year, 'actor_user_id' => $actorUserId],
            );

            return $formatted;
        });
    }
}
