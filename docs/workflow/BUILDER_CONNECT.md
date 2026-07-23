# Builder Connect API

REST endpoints for the React Flow workflow builder. These APIs manage workflow **metadata and draft JSON**; execution continues through the existing `WorkflowExecutor` runtime unchanged.

All routes require `auth:sanctum` (same as `/api/medicine-workflows`).

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/workflows` | Paginated workflow list |
| POST | `/api/workflows` | Create draft workflow |
| GET | `/api/workflows/{id}` | Load workflow for edit (draft configuration) |
| PUT | `/api/workflows/{id}` | Save draft |
| DELETE | `/api/workflows/{id}` | Delete workflow |
| POST | `/api/workflows/{id}/publish` | Validate, compile, publish immutable version |
| GET | `/api/workflow/triggers` | Trigger catalog + property-panel field schemas |
| GET | `/api/workflow/variables?trigger=` | Grouped variables for template picker |
| GET | `/api/workflow/templates` | Template list (`channel`, `category` filters) |
| POST | `/api/workflow/templates/{id}/preview` | Resolve template variables |
| GET | `/api/workflow/executions` | Paginated execution log |

Legacy read-only paths under `/api/hospital-automation/*` remain available during migration.

## Request body (create / save)

Same JSON contract as medicine workflows:

```json
{
  "name": "Appointment Booked Confirmation",
  "organization_id": 1,
  "configuration": {
    "builderVersion": "1",
    "reactFlowVersion": "12.x",
    "viewport": { "x": 0, "y": 0, "zoom": 1 },
    "nodes": [
      {
        "id": "t1",
        "type": "workflow",
        "position": { "x": 100, "y": 100 },
        "data": { "nodeType": "appointmentBooked" }
      }
    ],
    "edges": [
      { "id": "e1", "source": "t1", "target": "d1" }
    ]
  }
}
```

Trigger-specific property-panel fields from the Trigger Event Forms spec are stored inside `node.data` and are not reshaped by the backend.

Sample graph: [`samples/appointment-booked-confirmation.json`](samples/appointment-booked-confirmation.json).

## Response shapes

### List row (`GET /api/workflows`)

```json
{
  "id": 1,
  "name": "Appointment Booked Confirmation",
  "module": "appointment",
  "trigger_type": "appointmentBooked",
  "trigger_label": "Appointment Booked",
  "status": "draft",
  "organization_id": null,
  "current_version_number": null,
  "published_at": null,
  "updated_at": "2026-07-21T12:00:00+00:00"
}
```

### Detail (`GET /api/workflows/{id}`)

Returns `configuration` from the draft version when present, otherwise the published version. Includes `draft_version` and `published_version` metadata blocks.

### Publish (`POST /api/workflows/{id}/publish`)

Optional body: `{ "version_notes": "Initial release" }`

```json
{
  "workflow_id": 1,
  "version_id": 5,
  "version_number": 1,
  "published_at": "2026-07-21T12:00:00+00:00",
  "validation": {
    "valid": true,
    "errors": [],
    "warnings": []
  }
}
```

Publish validation mirrors frontend basic checks:

- Exactly one trigger node
- At least one end node
- Connected edges
- Compiler must succeed (`WorkflowCompiler::compile()`)

On success: creates an immutable published version, sets `workflows.current_version_id`, and sets workflow `status` to `active`.

### Triggers (`GET /api/workflow/triggers`)

```json
{
  "modules": {
    "appointment": ["appointmentBooked", "appointmentCancelled"]
  },
  "triggers": [
    {
      "type": "appointmentBooked",
      "label": "Appointment Booked",
      "module": "appointment",
      "description": "Fires when a new appointment is booked.",
      "fields": [
        { "key": "source", "label": "Source", "type": "select", "options": ["all", "doctor", "diagnostic"] }
      ]
    }
  ]
}
```

### Variables (`GET /api/workflow/variables?trigger=appointmentBooked`)

```json
{
  "trigger": "appointmentBooked",
  "groups": {
    "patient": [{ "key": "PatientName", "label": "Patient Name", "example": "John Doe" }],
    "appointment": [{ "key": "AppointmentDate", "label": "Appointment Date", "example": "2026-07-21" }]
  }
}
```

## Draft vs published versions

| Action | Version row | `workflows.status` |
|--------|-------------|-------------------|
| Create / Save | Upsert draft (`version_number = 0`, `status = draft`) | `draft` |
| Publish | New row (`version_number` increments, `status = published`) | `active` |

Running executions keep the `workflow_version_id` they started with.

## Module layout

```
app/Modules/Workflow/
├── Controllers/WorkflowBuilderController.php
├── Requests/StoreWorkflowRequest.php
├── Requests/UpdateWorkflowRequest.php
├── Resources/WorkflowResource.php
├── Resources/WorkflowDetailResource.php
├── Services/Builder/
│   ├── WorkflowBuilderService.php
│   ├── WorkflowPublishService.php
│   ├── TriggerSchemaRegistry.php
│   ├── VariableCatalogService.php
│   └── WorkflowTemplateQueryService.php
└── Routes/workflow.php
```

## Frontend integration checklist

1. **Workflow List** → `GET /api/workflows`
2. **Builder load/save** → `GET/PUT /api/workflows/{id}`
3. **Properties panel** → field schemas from `GET /api/workflow/triggers`
4. **Trigger selector** → same triggers API (no hardcoded list)
5. **Variable picker** → `GET /api/workflow/variables?trigger=`
6. **Template picker** → `GET /api/workflow/templates`
7. **Publish** → `POST /api/workflows/{id}/publish` (surface server validation errors)

See also: [Hospital Automation](HOSPITAL_AUTOMATION.md) · [Architecture](ARCHITECTURE.md)

## Testing

```bash
php artisan test --filter=WorkflowBuilderTest
```

Feature tests migrate only the organizations + workflow tables (SQLite in-memory). MySQL-only data migrations are skipped when the driver is not MySQL/MariaDB.

Tests bypass Sanctum by disabling `Illuminate\Auth\Middleware\Authenticate` (Laravel 12 requires the middleware **class**, not the `auth:sanctum` alias).

For queued workflow steps (delays, async listeners):

```bash
php artisan queue:work
```
