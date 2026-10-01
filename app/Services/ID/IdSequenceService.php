<?php

namespace App\Services\ID;

use App\Models\IdSequence;
use Illuminate\Support\Facades\DB;

/**
 * IdSequenceService — atomic sequence management.
 *
 * Spec §5.1: Official IDs are sequential, unique, never reused.
 *
 * Concurrency: uses SELECT ... FOR UPDATE inside a transaction so concurrent
 * requests serialize on the sequence row. The value is "burned" even on
 * rollback (audit integrity > zero-gap); a small gap is preferable to
 * duplicate IDs.
 */
class IdSequenceService
{
    /**
     * Lock + read the next value for a sequence key (call advance() after).
     */
    public function lockNext(string $key): IdSequence
    {
        return DB::transaction(function () use ($key) {
            $seq = IdSequence::lockForUpdate()->where('sequence_key', $key)->first();
            if (!$seq) {
                throw new \RuntimeException("Sequence not found: {$key}");
            }
            return $seq;
        });
    }

    public function advance(string $key, int $by = 1): void
    {
        IdSequence::where('sequence_key', $key)->increment('next_value', $by, [
            'last_used_at' => now(),
        ]);
    }

    /**
     * Convenience: get next value + advance in one transactional call.
     */
    public function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $seq = IdSequence::lockForUpdate()->where('sequence_key', $key)->firstOrFail();
            $value = $seq->next_value;
            $seq->next_value = $value + 1;
            $seq->last_used_at = now();
            $seq->save();
            return $value;
        });
    }

    public function ensureExists(string $key, string $prefix, int $padding = 6): void
    {
        IdSequence::firstOrCreate(
            ['sequence_key' => $key],
            ['prefix' => $prefix, 'padding' => $padding, 'next_value' => 1],
        );
    }
}
