@section('title', $pageTitle)
@section('breadcrumb', 'Dashboard / Automation / Workflow Templates / '.$pageTitle)

<div class="space-y-4">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <a href="{{ route('admin.automation.workflow-templates.index') }}"
               class="text-sm text-blue-600 hover:underline inline-flex items-center gap-1 mb-1">
                <i class="fas fa-arrow-left"></i> Back to templates
            </a>
            <h1 class="text-xl font-bold text-gray-900">{{ $pageTitle }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Template Mode — shared React Flow builder.
                @if ($readOnly)
                    Read-only preview.
                @else
                    Use <strong>Save Template</strong> (no Publish).
                @endif
            </p>
        </div>
    </div>

    <x-automation.builder-frame
        :embed-url="$embedUrl"
        mode="template"
        :read-only="$readOnly"
        :initial-payload="$initialPayload"
        save-method="saveFromBuilder"
    />
</div>
