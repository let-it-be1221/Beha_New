<?php

/**
 * Beha — organization-level configuration.
 *
 * All organization-specific business rules MUST be configurable here (spec §35).
 * Do not hard-code organization-specific business rules in controllers or services.
 */

return [

    'name'      => env('ORG_NAME', 'Beha Real Estate'),
    'logo_path' => env('ORG_LOGO_PATH', 'images/logo.png'),

    /*
    |--------------------------------------------------------------------------
    | Official ID configuration (spec §5.1)
    |--------------------------------------------------------------------------
    */
    'official_id' => [
        'prefix'    => env('OFFICIAL_ID_PREFIX', 'BH'),
        'pad_length' => (int) env('OFFICIAL_ID_PAD_LENGTH', 6),
        'separator' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer reference code (spec §9)
    |--------------------------------------------------------------------------
    */
    'customer_ref' => [
        'prefix'    => env('CUSTOMER_REF_PREFIX', 'CUS'),
        'pad_length'=> 6,
        'separator' => '-',
        'include_year' => true,
        'year_format' => 'Y', // 4-digit year
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset code (spec §12)
    |--------------------------------------------------------------------------
    */
    'asset_code' => [
        'prefix'    => env('ASSET_CODE_PREFIX', 'AST'),
        'pad_length'=> 6,
        'separator' => '-',
        'include_year' => true,
        'year_format' => 'Y',
    ],

    /*
    |--------------------------------------------------------------------------
    | Confidential ID (spec §6)
    |--------------------------------------------------------------------------
    | Format placeholders: {yy} year-2digit, {GG} generation, {BB} branch,
    |                      {TT} team, {RR} performance rank
    */
    'confidential_id' => [
        'format'    => env('CONFIDENTIAL_ID_FORMAT', '{yy}-{GG}-{BB}-{TT}-{RR}'),
        'delimiter' => env('CONFIDENTIAL_ID_DELIMITER', '-'),
        'pad_length' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Organizational capacity (spec §16)
    |--------------------------------------------------------------------------
    */
    'capacity' => [
        'team_members_per_team'         => (int) env('TEAM_MEMBER_CAPACITY', 10),
        'teams_per_branch'              => (int) env('TEAM_CAPACITY_PER_BRANCH', 10),
        'branches_per_generation'      => (int) env('BRANCH_CAPACITY_PER_GENERATION', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | User levels (spec §3)
    |--------------------------------------------------------------------------
    */
    'levels' => [
        1 => 'Team Member',
        2 => 'Senior Team Member',
        3 => 'Team Leader',
        4 => 'Branch Leader',
        5 => 'Generation Leader',
    ],

    /*
    |--------------------------------------------------------------------------
    | Horizontal roles (spec §2.A)
    |--------------------------------------------------------------------------
    */
    'horizontal_roles' => [
        'system_administrator',
        'finance_officer',
        'executive_officer',
        'record_officer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles allowed to view Confidential IDs (spec §6)
    |--------------------------------------------------------------------------
    */
    'confidential_id_viewers' => [
        'executive_officer',
        'record_officer',
        'finance_officer',
        'system_administrator',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password policy
    |--------------------------------------------------------------------------
    */
    'password_policy' => [
        'min_length'           => (int) env('PASSWORD_MIN_LENGTH', 12),
        'mixed_case'           => (bool) env('PASSWORD_REQUIRE_MIXED_CASE', true),
        'require_number'       => (bool) env('PASSWORD_REQUIRE_NUMBER', true),
        'require_symbol'       => (bool) env('PASSWORD_REQUIRE_SYMBOL', true),
        'temp_password_ttl'    => (int) env('TEMP_PASSWORD_TTL_HOURS', 24),
        'force_change_on_first_login' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Login attempt rate-limiting
    |--------------------------------------------------------------------------
    */
    'login_throttle' => [
        'max_attempts'  => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => (int) env('LOGIN_DECAY_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),
    ],
];
