<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\Customer;
use App\Models\Property;
use App\Models\WorkflowInstance;
use App\Policies\ApplicantPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkflowPolicy;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Policy mappings — every model that needs server-side authorization
     * gets its own Policy class. Spec §21, §25.
     */
    protected $policies = [
        User::class              => UserPolicy::class,
        Customer::class          => CustomerPolicy::class,
        Property::class          => PropertyPolicy::class,
        Applicant::class         => ApplicantPolicy::class,
        WorkflowInstance::class  => WorkflowPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Blade directive: @canViewConfidential($targetUser)
        \Blade::if('canviewconfidential', function (?User $target = null) {
            $viewer = auth()->user();
            if (!$viewer || !$target) return false;
            return app(UserPolicy::class)->viewConfidential($viewer, $target);
        });
    }
}
