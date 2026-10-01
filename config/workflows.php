<?php

/**
 * Beha — workflow engine configuration.
 *
 * Maps workflow "type" → definition. Each workflow definition lists its
 * steps, transitions, approver roles, and SLA in days.
 *
 * Do NOT hard-code workflow steps in controllers. Add a new entry here when a
 * new form / workflow is introduced (spec §23, §39).
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Workflow definitions
    |--------------------------------------------------------------------------
    | Each entry is keyed by a stable workflow_type string stored on the
    | `workflow_instances` table. The definition declares the ordered steps
    | and which role may act at each step.
    */
    'definitions' => [

        'customer_registration' => [
            'label'      => 'Customer Registration',
            'steps'      => [
                1 => ['name' => 'draft',                'actor' => 'team_member',          'action' => 'submit'],
                2 => ['name' => 'pending_team_evaluation', 'actor' => 'team_leader',        'action' => 'evaluate'],
                3 => ['name' => 'approved_by_team',     'actor' => 'team_leader',           'action' => 'forward'],
                4 => ['name' => 'pending_record_approval', 'actor' => 'record_officer',     'action' => 'verify'],
                5 => ['name' => 'registered',            'actor' => 'record_officer',       'action' => 'finalize'],
            ],
            'rejectable' => true,
            'correction' => true,
            'sla_days'   => 5,
        ],

        'property_registration' => [
            'label'      => 'Property Registration',
            'steps'      => [
                1 => ['name' => 'draft',                'actor' => 'generation_leader',     'action' => 'submit'],
                2 => ['name' => 'pending_verification', 'actor' => 'executive_officer',     'action' => 'verify'],
                3 => ['name' => 'verified',            'actor' => 'executive_officer',     'action' => 'forward'],
                4 => ['name' => 'pending_asset_coding','actor' => 'record_officer',        'action' => 'assign_asset_code'],
                5 => ['name' => 'published',            'actor' => 'record_officer',        'action' => 'publish'],
            ],
            'rejectable' => true,
            'correction' => true,
            'sla_days'   => 7,
        ],

        'applicant_onboarding' => [
            'label'      => 'Applicant Onboarding',
            'steps'      => [
                1 => ['name' => 'draft',                'actor' => 'applicant',             'action' => 'submit'],
                2 => ['name' => 'under_review',         'actor' => 'team_leader',           'action' => 'screen'],
                3 => ['name' => 'team_leader_screening', 'actor' => 'team_leader',         'action' => 'approve'],
                4 => ['name' => 'branch_assignment',    'actor' => 'branch_leader',         'action' => 'assign'],
                5 => ['name' => 'record_verification',  'actor' => 'record_officer',        'action' => 'generate_ids'],
                6 => ['name' => 'account_creation',     'actor' => 'system_administrator',   'action' => 'create_account'],
                7 => ['name' => 'approved',             'actor' => 'system_administrator',   'action' => 'notify'],
            ],
            'rejectable' => true,
            'correction' => true,
            'sla_days'   => 10,
        ],

        'promotion' => [
            'label'      => 'Promotion / Evaluation',
            'steps'      => [
                1 => ['name' => 'evaluation_initiated', 'actor' => 'team_leader',         'action' => 'evaluate'],
                2 => ['name' => 'evaluation_scored',    'actor' => 'team_leader',         'action' => 'submit'],
                3 => ['name' => 'pending_approval',     'actor' => 'branch_leader',       'action' => 'approve'],
                4 => ['name' => 'promoted',             'actor' => 'branch_leader',       'action' => 'finalize'],
            ],
            'rejectable' => true,
            'correction' => false,
            'sla_days'   => 7,
        ],

        'id_generation' => [
            'label'      => 'ID Generation (Record Officer)',
            'steps'      => [
                1 => ['name' => 'pending',  'actor' => 'record_officer', 'action' => 'verify'],
                2 => ['name' => 'generated','actor' => 'record_officer', 'action' => 'finalize'],
            ],
            'rejectable' => false,
            'correction' => false,
            'sla_days'   => 1,
        ],

        'property_publication' => [
            'label'      => 'Property Publication',
            'steps'      => [
                1 => ['name' => 'pending_publish', 'actor' => 'record_officer',   'action' => 'publish'],
                2 => ['name' => 'published',       'actor' => 'record_officer',   'action' => 'finalize'],
            ],
            'rejectable' => false,
            'correction' => false,
            'sla_days'   => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Self-approval prevention (spec §41 rule #11)
    |--------------------------------------------------------------------------
    */
    'prevent_self_approval' => true,
];
