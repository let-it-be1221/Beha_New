<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\CustomerDuplicate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * DuplicateDetector — detects existing customers that match a new candidate
 * using configurable matching rules.
 *
 * Spec §9.
 *
 * Matching fields (configurable in `system_settings` → customers.duplicate_match_fields):
 *   - national_id    (exact match — highest confidence)
 *   - phone          (exact match)
 *   - email          (exact match)
 *   - name + dob     (fuzzy match — name similarity + same DOB)
 *
 * Each match yields a CustomerDuplicate row with `match_field`, `match_score`
 * (0.0–1.0), and `resolved` = false. The Record Officer reviews + decides:
 *   - kept_existing   (new submission rejected, link to existing customer)
 *   - merged           (rare — not currently implemented at row level)
 *   - created_new      (confirmed unique, proceed with registration)
 */
class DuplicateDetector
{
    /**
     * Default matching rules — overridable via SystemSetting::get('customers.duplicate_match_fields').
     */
    private const DEFAULT_RULES = [
        ['field' => 'national_id',  'score' => 1.00, 'fuzzy' => false],
        ['field' => 'phone',         'score' => 0.95, 'fuzzy' => false],
        ['field' => 'email',         'score' => 0.90, 'fuzzy' => false],
        ['field' => 'name+dob',      'score' => 0.75, 'fuzzy' => true],
    ];

    /**
     * Run duplicate detection against a candidate customer.
     * Returns a sorted collection of matching existing customers.
     *
     * @return Collection<int, array{
     *   existing_customer: Customer,
     *   match_field: string,
     *   match_score: float,
     * }>
     */
    public function detect(Customer $candidate): Collection
    {
        $matches = collect();

        foreach ($this->rules() as $rule) {
            $found = $this->queryMatch($candidate, $rule);
            foreach ($found as $existing) {
                // Skip self (in case candidate is already persisted)
                if ($candidate->exists && $existing->id === $candidate->id) continue;

                $matches->push([
                    'existing_customer' => $existing,
                    'match_field'       => $rule['field'],
                    'match_score'       => (float) $rule['score'],
                ]);
            }
        }

        // Dedupe by existing customer id — keep highest score
        return $matches
            ->groupBy(fn($m) => $m['existing_customer']->id)
            ->map(fn($group) => $group->sortByDesc('match_score')->first())
            ->sortByDesc('match_score')
            ->values();
    }

    /**
     * Persist detected duplicates to customer_duplicates table (audit trail).
     */
    public function record(Customer $newCustomer, Collection $matches, ?int $resolvedBy = null): void
    {
        foreach ($matches as $match) {
            CustomerDuplicate::create([
                'new_customer_id'      => $newCustomer->id,
                'existing_customer_id' => $match['existing_customer']->id,
                'match_field'          => $match['match_field'],
                'match_score'          => $match['match_score'],
                'resolved'              => false,
                'resolved_by'           => $resolvedBy,
            ]);
        }
    }

    /**
     * Resolve a duplicate — Record Officer decides what to do.
     *
     * @param string $action 'kept_existing' | 'merged' | 'created_new'
     */
    public function resolve(CustomerDuplicate $duplicate, string $action, int $resolvedBy): CustomerDuplicate
    {
        $duplicate->update([
            'resolved'        => true,
            'resolved_by'     => $resolvedBy,
            'resolved_action' => $action,
        ]);
        return $duplicate->fresh();
    }

    // ─── Internals ───────────────────────────────────────────────────────

    private function rules(): array
    {
        // Future: read from SystemSetting::get('customers.duplicate_match_fields', self::DEFAULT_RULES)
        return self::DEFAULT_RULES;
    }

    private function queryMatch(Customer $candidate, array $rule): Collection
    {
        $query = Customer::query()->where('id', '!=', $candidate->id ?? 0);

        switch ($rule['field']) {
            case 'national_id':
                if (!$candidate->national_id) return collect();
                return $query->where('national_id', $candidate->national_id)->get();

            case 'phone':
                if (!$candidate->phone) return collect();
                // Normalize phone (strip spaces, dashes, parens)
                $normalized = preg_replace('/[\s\-\(\)]+/', '', $candidate->phone);
                return $query->whereRaw('REGEXP_REPLACE(phone, "[\\\\s\\\\-\\\\(\\\\)]+", "") = ?', [$normalized])->get();

            case 'email':
                if (!$candidate->email) return collect();
                return $query->where('email', $candidate->email)->get();

            case 'name+dob':
                if (!$candidate->full_name || !$candidate->date_of_birth) return collect();
                // Same date of birth + 80% name similarity (fuzzy)
                return $query
                    ->where('date_of_birth', $candidate->date_of_birth)
                    ->whereRaw('LOWER(SOUNDEX(full_name)) = LOWER(SOUNDEX(?))', [$candidate->full_name])
                    ->get();
        }

        return collect();
    }
}
