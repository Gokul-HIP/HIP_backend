# Real notification automation testing

Manual utility for sending **real** notifications through the production automation runtime to **dedicated test accounts only**.

This is separate from the mocked PHPUnit graph suite (`WorkflowAutomationCoverageTest`, etc.). Those suites must stay mocked and must not be weakened.

## Safety rules

- Keep `AUTOMATION_TEST_ENABLED=false` except while you are actively testing.
- Recipients come only from env / `config/automation.php` test accounts.
- The runner refuses to start if test mode is off, the account is missing, or required recipients for the selected channels are empty.
- Context never includes a real `DoctorBooking` / `Persons` model (no patient contact leak).
- Ephemeral graphs set **concrete** test recipients on messaging nodes.
- Every run sets `meta.source = automation_test` (visible in provider payloads / logs).
- Ephemeral workflows are stored with `workflows.source_type = automation_test` and are **excluded** from production `AutomationEngine::handle()` discovery.
- Ephemeral workflows are deactivated after sync (and after `--queue` job) via try/finally.
- `--workflow-id` only accepts workflows already marked `source_type=automation_test`.
- Logs are prefixed with `[automation_test]`.

## Isolation mechanism

| Layer | Behavior |
|-------|----------|
| `workflows.source_type` | Set to `automation_test` for ephemeral (and required for `--workflow-id`) |
| `WorkflowRepository::findPublishedByTrigger` | Excludes `source_type = automation_test` |
| `AutomationEngine::executeWorkflow` | Can still run a specific test workflow explicitly |
| Context | Synthetic contacts only; never loads patient models for recipients |

## 1. Configure a test account

In `.env` (placeholder values only):

```env
AUTOMATION_TEST_ENABLED=false
AUTOMATION_TEST_HOSPITAL_ID=123
AUTOMATION_TEST_ORGANIZATION_ID=1
AUTOMATION_TEST_USER_ID=your-test-user-id
AUTOMATION_TEST_PHONE=+10000000000
AUTOMATION_TEST_EMAIL=you@example.com
AUTOMATION_TEST_WHATSAPP=+10000000000
AUTOMATION_TEST_DEVICE_TOKEN=
```

Optional second account (`--account=2`):

```env
AUTOMATION_TEST_2_USER_ID=
AUTOMATION_TEST_2_PHONE=
AUTOMATION_TEST_2_EMAIL=
AUTOMATION_TEST_2_WHATSAPP=
AUTOMATION_TEST_2_DEVICE_TOKEN=
```

`AUTOMATION_TEST_HOSPITAL_ID` is required when the command creates an ephemeral workflow (default).

If `AUTOMATION_TEST_DEVICE_TOKEN` is set with a user id, the runner upserts a temporary `UserDevice` row for that test user so push can resolve an FCM token.

## 2. Enable real notification testing

```env
AUTOMATION_TEST_ENABLED=true
```

## 3. Run the command (sync — recommended)

```bash
php artisan automation:test appointmentBooked --account=1
```

Path:

`AutomationEngine::executeWorkflow` → `WorkflowExecutor` → `NodeExecutorRegistry` → messaging executors → `ChannelManager` → existing providers.

Ephemeral workflow is deactivated in a `finally` block (success, failure, or exception).

## 4. Test one channel

```bash
php artisan automation:test appointmentBooked --account=1 --channels=email
php artisan automation:test appointmentBooked --account=1 --channels=sms
php artisan automation:test appointmentBooked --account=1 --channels=push
php artisan automation:test appointmentBooked --account=1 --channels=whatsapp
```

## 5. Test a complete workflow (all channels)

```bash
php artisan automation:test appointmentBooked --account=1
```

## 6. Disable immediately

```env
AUTOMATION_TEST_ENABLED=false
```

## 7. Optional queue path (does not start a worker)

```bash
php artisan automation:test appointmentBooked --account=1 --queue
```

Dispatches `RunAutomationTestJob` only. The job cleans up ephemeral workflows in `finally` (including on failure). Start a worker yourself if needed:

```bash
php artisan queue:work --once
```

Until the job runs, an ephemeral workflow may remain active, but it stays `source_type=automation_test` and cannot be discovered by production `handle()`.

## 8. `--workflow-id` (advanced)

Only workflows with `source_type=automation_test` are accepted. Ordinary production workflows are rejected.

Prefer the default ephemeral path on servers.

## Automated tests (mocked — safe for CI)

```bash
php artisan test --filter=RealNotificationAutomationTest
```

## Related files left untouched (by design)

- `tests/Feature/AppointmentBookedAutomationTest.php` (still must pass)
- Graph coverage suites
- Global `ChannelManager` recipient resolution
- Production booking → observer → listener path (except excluding test-marked workflows from discovery)
