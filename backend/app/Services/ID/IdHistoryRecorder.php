<?php

namespace App\Services\ID;

use App\Models\IdGenerationHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Records every ID generation to id_generation_history (audit).
 */
class IdHistoryRecorder
{
    public function record(
        string $sequenceKey,
        string $generatedValue,
        string $subjectType,
        int $subjectId,
        ?int $actorUserId = null,
        ?array $payload = null,
    ): void {
        IdGenerationHistory::create([
            'sequence_key'    => $sequenceKey,
            'generated_value' => $generatedValue,
            'subject_type'    => $subjectType,
            'subject_id'      => $subjectId,
            'actor_user_id'   => $actorUserId ?? auth()->id(),
            'ip_address'      => Request::ip(),
            'user_agent'      => Request::userAgent(),
            'payload'         => $payload,
        ]);
    }
}
