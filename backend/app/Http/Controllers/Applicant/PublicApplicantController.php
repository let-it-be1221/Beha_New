<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Applicant\StoreApplicantRequest;
use App\Models\Applicant;
use App\Services\ID\IdSequenceService;
use Illuminate\Support\Str;

/**
 * PublicApplicantController — public self-signup form (spec §14).
 */
class PublicApplicantController extends Controller
{
    public function __construct(private IdSequenceService $sequences) {}

    public function create()
    {
        return view('applicants.apply');
    }

    public function store(StoreApplicantRequest $request)
    {
        $applicant = \DB::transaction(function () use ($request) {
            $year = now()->year;
            $this->sequences->ensureExists("app:{$year}", 'APP', 6);
            $seqValue = $this->sequences->next("app:{$year}");
            $code = "APP-{$year}-" . str_pad((string) $seqValue, 6, '0', STR_PAD_LEFT);

            return Applicant::create(array_merge($request->validated(), [
                'application_code'    => $code,
                'status'              => \App\Enums\ApplicantStatus::Submitted,
                'terms_accepted_at'   => now(),
            ]));
        });

        return redirect()->route('home')
            ->with('success', 'Application submitted. Your reference is ' . $applicant->application_code . '. A Team Leader will be in touch shortly.');
    }
}
