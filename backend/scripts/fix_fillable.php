<?php

/**
 * One-shot fix: add proper $fillable arrays to all stub models that
 * were batch-created with empty $fillable = [].
 */

$models = [
    'WorkflowStep' => ['workflow_instance_id', 'step_index', 'step_name', 'actor_role', 'expected_action', 'completed_at', 'completed_by'],
    'WorkflowAction' => ['workflow_instance_id', 'actor_user_id', 'action', 'from_step', 'to_step', 'comment', 'ip_address', 'user_agent'],
    'WorkflowComment' => ['workflow_instance_id', 'author_user_id', 'body'],
    'WorkflowAttachment' => ['workflow_instance_id', 'uploaded_by', 'file_path', 'file_name', 'mime_type', 'size_bytes'],
    'WorkflowHistory' => ['workflow_instance_id', 'from_step', 'to_step', 'actor_user_id', 'action', 'metadata'],
    'WorkflowDefinition' => ['code', 'label', 'config', 'is_active'],
    'CustomerDocument' => ['customer_id', 'document_type', 'file_path', 'file_name', 'mime_type', 'size_bytes', 'uploaded_by'],
    'CustomerReference' => ['customer_id', 'reference_code', 'issued_by', 'note'],
    'CustomerReview' => ['customer_id', 'reviewer_user_id', 'review_stage', 'decision', 'comment'],
    'CustomerDuplicate' => ['new_customer_id', 'existing_customer_id', 'match_field', 'match_score', 'resolved', 'resolved_by', 'resolved_action'],
    'PropertyDocument' => ['property_id', 'document_type', 'file_path', 'file_name', 'mime_type', 'size_bytes', 'uploaded_by'],
    'PropertyImage' => ['property_id', 'file_path', 'caption', 'is_primary', 'sort_order'],
    'PropertyVerification' => ['property_id', 'verifier_user_id', 'decision', 'comment'],
    'PropertyAsset' => ['property_id', 'asset_code', 'assigned_by'],
    'PropertyPublication' => ['property_id', 'published_by', 'published_at', 'unpublished_by', 'unpublished_at'],
    'ApplicantDocument' => ['applicant_id', 'document_type', 'file_path', 'file_name', 'mime_type', 'size_bytes', 'uploaded_by'],
    'ApplicantReview' => ['applicant_id', 'reviewer_user_id', 'review_stage', 'decision', 'comment'],
    'ApplicantAssignment' => ['applicant_id', 'assigner_user_id', 'generation_id', 'branch_id', 'team_id'],
    'EvaluationScore' => ['evaluation_id', 'criterion_id', 'score', 'comment'],
    'LevelPromotion' => ['team_member_id', 'evaluation_id', 'from_level', 'to_level', 'decision', 'approved_by', 'notes'],
    'PerformanceRecord' => ['team_member_id', 'period_start', 'period_end', 'metric', 'value'],
    'PerformanceRanking' => ['team_member_id', 'rank_in_team', 'rank_in_branch', 'rank_in_generation', 'overall_score', 'recalculated_at'],
    'PerformanceRankingHistory' => ['team_member_id', 'rank_in_team', 'rank_in_branch', 'rank_in_generation', 'overall_score', 'reason'],
    'NotificationTemplate' => ['code', 'channel', 'subject', 'body', 'is_active', 'variables'],
    'UserLevel' => ['code', 'name', 'description', 'is_active'],
];

$base = __DIR__ . '/../app/Models/';
$patched = 0;
foreach ($models as $class => $fields) {
    $file = $base . $class . '.php';
    if (!file_exists($file)) {
        echo "  WARN {$class}.php not found\n";
        continue;
    }
    $content = file_get_contents($file);
    $fillableStr = "protected \$fillable = [\n        '" . implode("',\n        '", $fields) . "',\n    ];";
    $pattern = '/protected \$fillable = \[\];/';
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $fillableStr, $content);
        file_put_contents($file, $content);
        echo "  OK  {$class}.php + " . count($fields) . " fields\n";
        $patched++;
    } else {
        echo "  SKIP {$class}.php (already has fillable)\n";
    }
}
echo "\nPatched {$patched} models.\n";
