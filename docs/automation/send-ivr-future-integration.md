# sendIvr — future integration

**Decision (Step 4.2):** this project has **no IVR provider or backend integration**. `sendIvr` is **not** part of the Step 4 executable contract.

## Rules

1. Do **not** implement `sendIvr`.
2. Do **not** create a fake IVR executor or provider.
3. Frontend will hide/remove `sendIvr` from the workflow builder (frontend change, not this backend).
4. Backend `WorkflowPublishService::validate()` continues to reject `sendIvr` with `unsupported_node`.
5. Saved/draft workflows that still contain `sendIvr` remain **readable**. They are **not publishable** and **not executable** (`NodeProcessorRegistry` has no `sendIvr` handler; runtime fails closed).
6. No further Step 4 implementation work on `sendIvr`.
7. Treat IVR as a **future integration** when a real provider contract exists.

`sendAiVoice` is a different node (HTTP `WORKFLOW_AI_VOICE_ENDPOINT`). It is **not** an alias of `sendIvr`.

## Step 4 completion scope

All currently supported and **publishable** frontend node types are generically executable by the backend.

`sendIvr` is not a Step 4 blocker once the frontend no longer exposes it as a publishable node.
