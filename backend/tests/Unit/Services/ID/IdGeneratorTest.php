<?php

namespace Tests\Unit\Services\ID;

use App\Services\ID\AssetCodeGenerator;
use App\Services\ID\ConfidentialIdGenerator;
use App\Services\ID\CustomerReferenceGenerator;
use App\Services\ID\IdHistoryRecorder;
use App\Services\ID\IdSequenceService;
use App\Services\ID\OfficialIdGenerator;
use Tests\TestCase;

/**
 * Verifies the four ID generators follow spec §5.1, §6, §9, §12.
 */
class IdGeneratorTest extends TestCase
{
    private OfficialIdGenerator $official;
    private CustomerReferenceGenerator $customerRef;
    private AssetCodeGenerator $assetCode;
    private ConfidentialIdGenerator $confidential;

    protected function setUp(): void
    {
        parent::setUp();
        $seq = app(IdSequenceService::class);
        $hist = app(IdHistoryRecorder::class);
        $this->official    = new OfficialIdGenerator($seq, $hist);
        $this->customerRef = new CustomerReferenceGenerator($seq, $hist);
        $this->assetCode   = new AssetCodeGenerator($seq, $hist);
        $this->confidential = new ConfidentialIdGenerator($hist);
    }

    public function test_official_id_is_sequential_and_zero_padded(): void
    {
        $a = $this->official->next();
        $b = $this->official->next();
        $this->assertSame('BH000001', $a);
        $this->assertSame('BH000002', $b);
    }

    public function test_customer_reference_is_year_scoped(): void
    {
        $year = now()->year;
        $ref = $this->customerRef->next($year);
        $this->assertSame("CUS-{$year}-000001", $ref);
    }

    public function test_asset_code_is_year_scoped(): void
    {
        $year = now()->year;
        $code = $this->assetCode->next($year);
        $this->assertSame("AST-{$year}-000001", $code);
    }

    public function test_confidential_id_format_substitution(): void
    {
        $formatted = $this->confidential->format(2026, [
            'generation' => 3, 'branch' => 7, 'team' => 4, 'rank' => 2,
        ]);
        $this->assertSame('26-03-07-04-02', $formatted);
    }
}
