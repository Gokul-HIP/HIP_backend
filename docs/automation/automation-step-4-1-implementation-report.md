# Step 4.1 / 4.2 — Frontend-exposed node capabilities

Date: 2026-09-23

## Status

**A. STEP 4 COMPLETE FOR CURRENTLY SUPPORTED AND PUBLISHABLE FRONTEND CONTRACT**

All currently supported and publishable frontend node types are generically executable by the backend.

`sendIvr` is **not** a Step 4 blocker. There is no IVR provider. It is not implemented and not faked. Publish rejects it (`unsupported_node`). Runtime has no executor. Frontend will hide/remove it from the builder. See `docs/automation/send-ivr-future-integration.md`.

Step 5 was not started.

## Node capability matrix

| FE nodeType | Handling | Normalized | Executor | Result |
|---|---|---|---|---|
| `dbQuery` | D — new generic constrained executor | `dbQuery` | `DbQueryNodeProcessor` | Allowlisted entity locator; SQL strings fail |
| `sendIvr` | Future integration | `sendIvr` | none | Not publishable, not executable; no fake provider |
| `httpRequest` | B+C alias + generic HTTP options | `webhook` | `WebhookNodeProcessor` | URL/method/headers/body/timeout from node config |
| `webhook` | A existing | `webhook` | `WebhookNodeProcessor` | Retained for saved graphs |
| `updateAppointment` | B+C alias + domain map | `databaseUpdate` | `UpdateRecordNodeProcessor` | `doctor_bookings` + hospital scope |
| `updatePrescription` | B+C | `databaseUpdate` | `UpdateRecordNodeProcessor` | `prescriptions` + hospital scope |
| `updateMembership` | B+C | `databaseUpdate` | `UpdateRecordNodeProcessor` | `user_family_subscriptions` + member scope |
| `start` | Frontend-only | stripped | none | Compiler strips; publish skips |
| `dbUpdate` / `updateRecord` | A/B existing | `databaseUpdate` | `UpdateRecordNodeProcessor` | Generic table path preserved |

## JSON contracts

**dbQuery (fixture):** `{ "nodeType": "dbQuery", "query": "patient.id == context.patient.id" }`  
Also accepted: `entity`/`table` + `id`/`recordId`/`where.id`. Rejects `SELECT`/`INSERT`/… strings. Reads via Query Builder against `DomainRecordMap`. Sets `variables.query_matched`, `variables.query_row`.

**httpRequest (fixture):** `{ "nodeType": "httpRequest", "url": "https://example.com/api" }`  
Optional: `method`, `headers`, `body`, `params`/`query`, `timeout`. Default POST body matches existing webhook payload. http/https only.

**updateAppointment / updatePrescription / updateMembership (fixtures are label-only):** runtime requires `values`. Record id from `id`/`recordId`/`where.id` or context (`appointment_id`, `prescription_id`, `membership_id`). Original `data.nodeType` selects the allowlisted table.

**sendIvr:** drafts may still contain the node; cannot publish or execute.

**start:** canvas node; omitted from compiled graph.

## Publish validation

`WorkflowPublishService::validate()` requires `NodeProcessorRegistry::has($normalizedType)` for every non-frontend-only node. `sendIvr` fails with `unsupported_node`.
