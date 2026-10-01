<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a user attempts to approve their own submission
 * (spec §41 rule #11).
 */
class WorkflowSelfApprovalException extends RuntimeException
{
    //
}
