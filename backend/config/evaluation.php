<?php

/**
 * Beha — evaluation engine configuration.
 *
 * Spec §27 — evaluation criteria are configurable, NOT hard-coded.
 * The default weights below are illustrative; admins can override them via
 * `evaluation_templates` rows in the database.
 */
return [
    'default_template' => 'default_member_evaluation',

    'default_criteria' => [
        'sales_performance'    => ['weight' => 30, 'min' => 0, 'max' => 100, 'passing' => 60],
        'customer_registration'=> ['weight' => 20, 'min' => 0, 'max' => 100, 'passing' => 60],
        'customer_approval'    => ['weight' => 15, 'min' => 0, 'max' => 100, 'passing' => 60],
        'attendance'           => ['weight' => 10, 'min' => 0, 'max' => 100, 'passing' => 60],
        'team_contribution'    => ['weight' => 15, 'min' => 0, 'max' => 100, 'passing' => 60],
        'other'                => ['weight' => 10, 'min' => 0, 'max' => 100, 'passing' => 60],
    ],

    // Promotion thresholds — actual rules live in promotion_rules table
    'promotion_levels' => [
        1 => ['next_level' => 2, 'template' => 'l1_to_l2'],
        2 => ['next_level' => 3, 'template' => 'l2_to_l3'], // becomes Team Leader
        3 => ['next_level' => 4, 'template' => 'l3_to_l4'], // becomes Branch Leader
        4 => ['next_level' => 5, 'template' => 'l4_to_l5'], // becomes Generation Leader
    ],

    'rank_recalculation' => [
        'on_event' => true, // recalculate on sales / customer approval / evaluation save
        'batch_cron' => '0 2 * * *', // nightly at 2:00 AM safety net
    ],
];
