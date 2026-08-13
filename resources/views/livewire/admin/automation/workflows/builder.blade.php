@section('title', $pageTitle)
@section('breadcrumb', 'Dashboard / Automation / Workflows / '.$pageTitle)

<div class="space-y-4">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <a href="{{ route('admin.automation.workflows.index') }}"
           class="text-sm text-blue-600 hover:underline inline-flex items-center gap-1 mb-1">
            <i class="fas fa-arrow-left"></i> Back to workflows
        </a>
        <h1 class="text-xl font-bold text-gray-900">{{ $pageTitle }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Workflow Mode — shared React Flow builder.
            @unless ($readOnly)
                Use <strong>Save</strong> / <strong>Publish Workflow</strong> in the toolbar.
            @endunless
        </p>
    </div>

    <x-automation.builder-frame
        :embed-url="$embedUrl"
        mode="workflow"
        :read-only="$readOnly"
        :initial-payload="$initialPayload"
        save-method="saveFromBuilder"
        publish-method="publishFromBuilder"
    />
</div>
