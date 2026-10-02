<?php

namespace App\Services\ID;

use Illuminate\Support\Facades\DB;

/**
 * Asset Code Generator — AST-2026-000001 (spec §12).
 *
 * One sequence PER YEAR (key = 'ast:{year}'). Resets on Jan 1.
 * Issued ONLY after Executive Officer verifies the property.
 * Immutable after assignment — even if property is unpublished, the code is preserved.
 */
class AssetCodeGenerator
{
    public function __construct(
        private IdSequenceService $sequences,
        private IdHistoryRecorder $history,
    ) {}

    public function next(int $year, ?int $propertyId = null, ?int $actorUserId = null): string
    {
        $key     = "ast:{$year}";
        $prefix  = (string) config('beha.asset_code.prefix', 'AST');
        $padding = (int) config('beha.asset_code.pad_length', 6);

        $this->sequences->ensureExists($key, $prefix, $padding);

        return DB::transaction(function () use ($key, $prefix, $padding, $year, $propertyId, $actorUserId) {
            $value = $this->sequences->next($key);
            $formatted = "{$prefix}-{$year}-" . str_pad((string) $value, $padding, '0', STR_PAD_LEFT);

            $this->history->record(
                sequenceKey: $key,
                generatedValue: $formatted,
                subjectType: 'App\\Models\\Property',
                subjectId: $propertyId ?? 0,
                payload: ['raw_value' => $value, 'year' => $year, 'actor_user_id' => $actorUserId],
            );

            return $formatted;
        });
    }
}
