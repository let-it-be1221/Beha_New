<?php

namespace App\Enums;

/**
 * Workflow action verbs (spec §23).
 */
enum WorkflowAction: string
{
    case Submit     = 'submit';
    case Approve    = 'approve';
    case Reject     = 'reject';
    case Forward    = 'forward';
    case Publish    = 'publish';
    case Verify     = 'verify';
    case Assign     = 'assign';
    case Screen     = 'screen';
    case Evaluate   = 'evaluate';
    case Generate   = 'generate';
    case Finalize   = 'finalize';
    case Resubmit   = 'resubmit';
    case RequestCorrection = 'request_correction';
}
