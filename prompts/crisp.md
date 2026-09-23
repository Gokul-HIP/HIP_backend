# TASK

Analyze the existing Laravel Automation Engine and Event system and make it properly support the saved workflow JSON contracts.

# CONTEXT

The frontend React Flow workflow builder is already completed.

The saved workflow JSON is the source of truth for backend execution. It contains the trigger, nodes, node configuration, conditions, waits, messaging actions, recipients, campaignKey, campaignStep, and edges/branches. :contentReference[oaicite:0]{index=0}

The Laravel backend already has an Automation Engine, Events, Listeners, Workflow Compiler, Workflow Executor, Node Executors, Condition Engine, Delay/Scheduler and Channel Manager.

Do not create a second or parallel automation engine.

# ROLE

Act as a Senior Laravel Developer.

# INSTRUCTION

First analyze the old/existing automation implementation completely.

Check:

- existing AutomationEngine
- events and event classes
- listeners / observers
- trigger dispatching
- workflow lookup
- workflow repository
- workflow compiler/executor
- node executors and registry
- condition handling
- delay/wait handling
- messaging/channel handling
- execution and duplicate prevention

Then read:

want to add the json file path

For every workflow JSON in that file:

1. Identify the trigger and determine which real Laravel event should start it.
2. Check whether that event, listener and automation flow already exist.
3. Check every JSON node and map it to the existing backend executor.
4. Check all conditions, variables, waits, recipients, campaignKey and campaignStep.
5. Check how the runtime context is built for the values used by the JSON.
6. Check how workflows are selected when multiple workflows use the same trigger.
7. Identify anything missing or incorrectly implemented in the current backend.

After the analysis, change the existing backend implementation wherever required so that the actual flow becomes:

Domain Event
→ Event / Listener / Scheduler
→ AutomationEngine
→ Build Runtime Context
→ Find Matching Workflows
→ Create Execution
→ Compile Saved JSON
→ Execute Trigger
→ Execute Nodes / Conditions / Waits / Actions
→ Send Message
→ Complete Execution

Important:

- Use the existing architecture wherever possible.
- Do not invent new workflow JSON structures.
- Do not hardcode workflow IDs or campaign-specific logic.
- Do not create duplicate executors or duplicate event systems.
- Do not change the frontend JSON just to make backend implementation easier.
- If something in the old implementation does not support the saved JSON, fix the backend properly.
- Make sure multiple workflows with the same trigger are handled correctly.
- Keep hospital/organization isolation and existing business rules intact.
- Use existing services and integrations wherever available.

Before coding, show the mapping of:

Trigger → Laravel Event → Listener → AutomationEngine → Workflow

and:

JSON nodeType → Existing Executor → Required Change

Then implement the required changes and test the actual event-to-workflow execution path using the saved workflow JSON.