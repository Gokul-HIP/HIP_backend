<?php

namespace App\Modules\Workflow\Contracts;

use App\Modules\Workflow\DTO\CompiledWorkflow;

interface WorkflowCompilerInterface
{
    /**
     * @param  array<string, mixed>  $definition  React Flow document
     */
    public function compile(array $definition): CompiledWorkflow;
}
