<?php

return [
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),
    'log_enabled' => env('ACTIVITY_LOGGER_LOG_ENABLED', true),
    'log_only_dirty' => env('ACTIVITY_LOGGER_LOG_ONLY_DIRTY', true),
    'log_attributes' => ['*'],
    'ignore_attributes' => ['password', 'remember_token', 'two_factor_secret'],
    'subject_returns_soft_deletes' => true,
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,
];
