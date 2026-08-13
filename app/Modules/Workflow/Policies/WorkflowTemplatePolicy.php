<?php

namespace App\Modules\Workflow\Policies;

use App\Modules\Workflow\Models\WorkflowTemplate;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authorization for workflow blueprint templates.
 * Matches existing FormRequest authorize() => true pattern; route auth:sanctum is the gate.
 */
class WorkflowTemplatePolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, WorkflowTemplate $template): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, WorkflowTemplate $template): bool
    {
        return true;
    }

    public function delete(?Authenticatable $user, WorkflowTemplate $template): bool
    {
        return true;
    }

    public function duplicate(?Authenticatable $user, WorkflowTemplate $template): bool
    {
        return true;
    }

    public function preview(?Authenticatable $user, WorkflowTemplate $template): bool
    {
        return true;
    }
}
