<?php

namespace Database\Seeders;

use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use Illuminate\Database\Seeder;

class HospitalAutomationWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $repository = app(WorkflowRepository::class);
        $samples = require database_path('seeders/data/hospital_automation_workflows.php');

        foreach ($samples as $sample) {
            $workflow = Workflow::query()->firstOrCreate(
                ['name' => $sample['name']],
                [
                    'organization_id' => null,
                    'status' => WorkflowStatus::Draft->value,
                    'trigger_type' => $sample['trigger_type'],
                ]
            );

            $repository->publishVersion(
                $workflow,
                $sample['definition'],
                publishedBy: null,
                versionNotes: 'Default hospital automation workflow'
            );
        }
    }
}
