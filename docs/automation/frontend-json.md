# Existing workflows

Inventory of every fixture in `automation/node-workflows/`. For each workflow: name, triggers, nodes, and the full JSON.

| # | Workflow | File | Trigger |
|---|----------|------|---------|
| 1 | Fixture — ai | `ai.workflow.json` | `appointmentBooked` |
| 2 | Fixture — anniversary | `anniversary.workflow.json` | `appointmentBooked` |
| 3 | Fixture — apiEvent | `apiEvent.workflow.json` | `appointmentBooked` |
| 4 | Fixture — appointmentBooked | `appointmentBooked.workflow.json` | `appointmentBooked` |
| 5 | Fixture — appointmentCancelled | `appointmentCancelled.workflow.json` | `appointmentBooked` |
| 6 | Fixture — appointmentCompleted | `appointmentCompleted.workflow.json` | `appointmentBooked` |
| 7 | Fixture — appointmentMissed | `appointmentMissed.workflow.json` | `appointmentBooked` |
| 8 | Fixture — appointmentReminder | `appointmentReminder.workflow.json` | `appointmentBooked` |
| 9 | Fixture — appointmentRescheduled | `appointmentRescheduled.workflow.json` | `appointmentBooked` |
| 10 | Fixture — birthday | `birthday.workflow.json` | `appointmentBooked` |
| 11 | Birthday Wish | `birthdayWish.workflow.json` | `birthday` |
| 12 | Fixture — condition | `condition.workflow.json` | `appointmentBooked` |
| 13 | Fixture — dbCreate | `dbCreate.workflow.json` | `appointmentBooked` |
| 14 | Fixture — dbDelete | `dbDelete.workflow.json` | `appointmentBooked` |
| 15 | Fixture — dbQuery | `dbQuery.workflow.json` | `appointmentBooked` |
| 16 | Fixture — dbUpdate | `dbUpdate.workflow.json` | `appointmentBooked` |
| 17 | Dentist Segment Campaign | `dentistSegment.workflow.json` | `appointmentBooked` |
| 18 | Digital Prescription Share | `digitalPrescriptionCampaign.workflow.json` | `prescriptionAdded` |
| 19 | Fixture — end | `end.workflow.json` | `appointmentBooked` |
| 20 | Fixture — familyPackageTierUpdated | `familyPackageTierUpdated.workflow.json` | `appointmentBooked` |
| 21 | First Appointment Nurturing | `firstAppointmentNurturing.workflow.json` | `patientRegistered` |
| 22 | Fixture — httpRequest | `httpRequest.workflow.json` | `appointmentBooked` |
| 23 | Inactive Patient 30 Days | `inactivePatient30.workflow.json` | `scheduledEvent` |
| 24 | Inactive Patient 90 Days | `inactivePatient90.workflow.json` | `scheduledEvent` |
| 25 | Fixture — invoiceGenerated | `invoiceGenerated.workflow.json` | `appointmentBooked` |
| 26 | Fixture — labReportNotification | `labReportNotification.workflow.json` | `appointmentBooked` |
| 27 | Fixture — labTestOrdered | `labTestOrdered.workflow.json` | `appointmentBooked` |
| 28 | Fixture — medicineReminder | `medicineReminder.workflow.json` | `appointmentBooked` |
| 29 | Medicine Reminder | `medicineReminderCampaign.workflow.json` | `medicineReminder` |
| 30 | Fixture — membershipExpiry | `membershipExpiry.workflow.json` | `appointmentBooked` |
| 31 | Missed Appointment Restart | `missedAppointmentRestart.workflow.json` | `appointmentMissed` |
| 32 | Fixture — onChatMessage | `onChatMessage.workflow.json` | `appointmentBooked` |
| 33 | Parent–Child Segment Campaign | `parentChildSegment.workflow.json` | `appointmentBooked` |
| 34 | Fixture — patientRegistered | `patientRegistered.workflow.json` | `appointmentBooked` |
| 35 | Fixture — paymentReceived | `paymentReceived.workflow.json` | `appointmentBooked` |
| 36 | Fixture — pharmacyRefillDue | `pharmacyRefillDue.workflow.json` | `appointmentBooked` |
| 37 | Post-Visit Follow-up | `postVisitFollowup.workflow.json` | `appointmentCompleted` |
| 38 | Fixture — prescriptionAdded | `prescriptionAdded.workflow.json` | `appointmentBooked` |
| 39 | Fixture — rewardUpdated | `rewardUpdated.workflow.json` | `appointmentBooked` |
| 40 | Fixture — rewardsTierUpgraded | `rewardsTierUpgraded.workflow.json` | `appointmentBooked` |
| 41 | Fixture — scheduledEvent | `scheduledEvent.workflow.json` | `appointmentBooked` |
| 42 | Segment Variants Example | `segmentDentistSeniorParentChild.workflow.json` | `appointmentBooked` |
| 43 | Fixture — sendAiChat | `sendAiChat.workflow.json` | `appointmentBooked` |
| 44 | Fixture — sendAiVoice | `sendAiVoice.workflow.json` | `appointmentBooked` |
| 45 | Fixture — sendEmail | `sendEmail.workflow.json` | `appointmentBooked` |
| 46 | Fixture — sendIvr | `sendIvr.workflow.json` | `appointmentBooked` |
| 47 | Fixture — sendPush | `sendPush.workflow.json` | `appointmentBooked` |
| 48 | Fixture — sendSms | `sendSms.workflow.json` | `appointmentBooked` |
| 49 | Fixture — sendTemplate | `sendTemplate.workflow.json` | `appointmentBooked` |
| 50 | Fixture — sendWhatsApp | `sendWhatsApp.workflow.json` | `appointmentBooked` |
| 51 | Senior Patient Segment Campaign | `seniorPatientSegment.workflow.json` | `appointmentBooked` |
| 52 | Fixture — start | `start.workflow.json` | `appointmentBooked` |
| 53 | Fixture — updateAppointment | `updateAppointment.workflow.json` | `appointmentBooked` |
| 54 | Fixture — updateMembership | `updateMembership.workflow.json` | `appointmentBooked` |
| 55 | Fixture — updatePrescription | `updatePrescription.workflow.json` | `appointmentBooked` |
| 56 | Fixture — userPlanExpiry | `userPlanExpiry.workflow.json` | `appointmentBooked` |
| 57 | Fixture — wait | `wait.workflow.json` | `appointmentBooked` |
| 58 | Fixture — webhookEvent | `webhookEvent.workflow.json` | `appointmentBooked` |
| 59 | Women's Day Wish | `womensDayWish.workflow.json` | `anniversary` |

---

## Fixture — ai

**File:** `ai.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `ai` — AI (ai)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — ai",
    "focusNodeType": "ai",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — ai",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_ai",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "ai",
            "category": "ai",
            "status": "draft",
            "label": "AI"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_ai",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_ai",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "ai",
      "end"
    ],
    "expectedOutput": {
      "ai": {
        "success": true,
        "stub": true
      }
    },
    "expectedNextNode": {
      "n_ai": "n_end"
    },
    "expectedFailureBehavior": "Stub currently does not fail"
  }
}
```

---

## Fixture — anniversary

**File:** `anniversary.workflow.json`

**Triggers**

- `appointmentBooked`
- `anniversary`

**Nodes**

- `anniversary` — Anniversary (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — anniversary",
    "focusNodeType": "anniversary",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — anniversary",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_anniversary",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "anniversary",
            "category": "triggers",
            "label": "Anniversary",
            "status": "draft",
            "anniversaryType": "registration",
            "triggerTiming": "on_date",
            "daysBefore": 1,
            "executionTime": "09:00",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_anniversary",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "anniversary"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "anniversary trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "anniversary"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_anniversary": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — apiEvent

**File:** `apiEvent.workflow.json`

**Triggers**

- `appointmentBooked`
- `apiEvent`

**Nodes**

- `apiEvent` — API Event (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — apiEvent",
    "focusNodeType": "apiEvent",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — apiEvent",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_apiEvent",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "apiEvent",
            "category": "triggers",
            "label": "API Event",
            "status": "draft",
            "eventName": "",
            "apiKey": "",
            "payloadVariables": [
              {
                "key": "",
                "variable": ""
              }
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_apiEvent",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "apiEvent"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "apiEvent trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "apiEvent"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_apiEvent": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — appointmentBooked

**File:** `appointmentBooked.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — appointmentBooked",
    "focusNodeType": "appointmentBooked",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — appointmentBooked",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "appointmentBooked"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "appointmentBooked"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_appointmentBooked": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws",
    "hospitalLookup": {
      "trigger_type": "appointmentBooked",
      "status": "published|active",
      "hospital_id": "booking.hospital_id (e.g. 12)",
      "noGlobalNullFallback": true
    }
  }
}
```

---

## Fixture — appointmentCancelled

**File:** `appointmentCancelled.workflow.json`

**Triggers**

- `appointmentBooked`
- `appointmentCancelled`

**Nodes**

- `appointmentCancelled` — Appointment Cancelled (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — appointmentCancelled",
    "focusNodeType": "appointmentCancelled",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — appointmentCancelled",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentCancelled",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentCancelled",
            "category": "triggers",
            "label": "Appointment Cancelled",
            "status": "draft",
            "source": [
              "any"
            ],
            "cancelledBy": [
              "any"
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentCancelled",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "appointmentCancelled"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentCancelled trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "appointmentCancelled"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_appointmentCancelled": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — appointmentCompleted

**File:** `appointmentCompleted.workflow.json`

**Triggers**

- `appointmentBooked`
- `appointmentCompleted`

**Nodes**

- `appointmentCompleted` — Appointment Completed (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — appointmentCompleted",
    "focusNodeType": "appointmentCompleted",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — appointmentCompleted",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentCompleted",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentCompleted",
            "category": "triggers",
            "label": "Appointment Completed",
            "status": "draft",
            "source": [
              "any"
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentCompleted",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "appointmentCompleted"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentCompleted trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "appointmentCompleted"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_appointmentCompleted": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — appointmentMissed

**File:** `appointmentMissed.workflow.json`

**Triggers**

- `appointmentBooked`
- `appointmentMissed`

**Nodes**

- `appointmentMissed` — Appointment Missed (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — appointmentMissed",
    "focusNodeType": "appointmentMissed",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — appointmentMissed",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentMissed",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentMissed",
            "category": "triggers",
            "label": "Appointment Missed",
            "status": "draft",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentMissed",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "appointmentMissed"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentMissed trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "appointmentMissed"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_appointmentMissed": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — appointmentReminder

**File:** `appointmentReminder.workflow.json`

**Triggers**

- `appointmentBooked`
- `appointmentReminder`

**Nodes**

- `appointmentReminder` — Appointment Reminder (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — appointmentReminder",
    "focusNodeType": "appointmentReminder",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — appointmentReminder",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentReminder",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentReminder",
            "category": "triggers",
            "label": "Appointment Reminder",
            "status": "draft",
            "triggerTiming": "before_24h",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentReminder",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "appointmentReminder"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentReminder trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "appointmentReminder"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_appointmentReminder": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — appointmentRescheduled

**File:** `appointmentRescheduled.workflow.json`

**Triggers**

- `appointmentBooked`
- `appointmentRescheduled`

**Nodes**

- `appointmentRescheduled` — Appointment Rescheduled (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — appointmentRescheduled",
    "focusNodeType": "appointmentRescheduled",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — appointmentRescheduled",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentRescheduled",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentRescheduled",
            "category": "triggers",
            "label": "Appointment Rescheduled",
            "status": "draft",
            "source": [
              "any"
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentRescheduled",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "appointmentRescheduled"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentRescheduled trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "appointmentRescheduled"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_appointmentRescheduled": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — birthday

**File:** `birthday.workflow.json`

**Triggers**

- `appointmentBooked`
- `birthday`

**Nodes**

- `birthday` — Birthday (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — birthday",
    "focusNodeType": "birthday",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — birthday",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_birthday",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "birthday",
            "category": "triggers",
            "label": "Birthday",
            "status": "draft",
            "triggerTiming": "on_birthday",
            "daysBefore": 1,
            "executionTime": "09:00",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_birthday",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "birthday"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "birthday trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "birthday"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_birthday": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Birthday Wish

**File:** `birthdayWish.workflow.json`

**Triggers**

- `birthday`

**Nodes**

- `birthday` — Birthday (triggers)
- `sendWhatsApp` — Birthday wish (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Birthday Wish",
    "focusNodeType": "birthday",
    "campaignKey": "birthday_wish"
  },
  "workflow": {
    "name": "Birthday Wish",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "birthday",
    "module": "engagement",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "birthday_wish",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "birthday",
            "category": "triggers",
            "label": "Birthday",
            "triggerTiming": "on_birthday",
            "daysBefore": 1,
            "executionTime": "09:00",
            "status": "draft"
          }
        },
        {
          "id": "n_msg",
          "type": "workflow",
          "position": {
            "x": 320,
            "y": 160
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Birthday wish",
            "templateId": "",
            "recipient": "patient",
            "message": "Happy Birthday {{patient_name}}! Wishing you good health from {{hospital_name}}.",
            "campaignStep": "birthday_1",
            "status": "draft"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 600,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_msg"
        },
        {
          "id": "e2",
          "source": "n_msg",
          "target": "n_end"
        }
      ]
    }
  }
}
```

---

## Fixture — condition

**File:** `condition.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `condition` — Confirmed Appointment (conditions)
- `end` — End True (flow)
- `end` — End False (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — condition",
    "focusNodeType": "condition",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — condition",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_condition",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "name": "Confirmed Appointment",
            "label": "Confirmed Appointment",
            "expression": "appointment.status == \"Confirmed\"",
            "status": "draft"
          }
        },
        {
          "id": "n_end_true",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 60
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End True",
            "outcome": "completed",
            "status": "ready"
          }
        },
        {
          "id": "n_end_false",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 260
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End False",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_condition",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e_true",
          "source": "n_condition",
          "target": "n_end_true",
          "type": "smoothstep",
          "sourceHandle": "true",
          "targetHandle": null
        },
        {
          "id": "e_false",
          "source": "n_condition",
          "target": "n_end_false",
          "type": "smoothstep",
          "sourceHandle": "false",
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "condition → branch true",
      "end true"
    ],
    "expectedOutput": {
      "condition": {
        "success": true,
        "result": true,
        "branch": "true",
        "currentImplementation": {
          "action": "branch",
          "branchHandle": "true",
          "output": {
            "passed": true,
            "branch": "true",
            "expression": "appointment.status == \"Confirmed\""
          }
        }
      }
    },
    "expectedNextNode": {
      "n_condition": "n_end_true"
    },
    "expectedFailureBehavior": "Empty/invalid JEXL → action=error; execution fails"
  }
}
```

---

## Fixture — dbCreate

**File:** `dbCreate.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `dbCreate` — Create Record (database)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — dbCreate",
    "focusNodeType": "dbCreate",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — dbCreate",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_dbCreate",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "dbCreate",
            "category": "database",
            "status": "draft",
            "label": "Create Record"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_dbCreate",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_dbCreate",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "dbCreate",
      "end"
    ],
    "expectedOutput": {
      "dbCreate": {
        "success": true,
        "stub": true
      }
    },
    "expectedNextNode": {
      "n_dbCreate": "n_end"
    },
    "expectedFailureBehavior": "Stub currently does not fail"
  }
}
```

---

## Fixture — dbDelete

**File:** `dbDelete.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `dbDelete` — Delete Record (database)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — dbDelete",
    "focusNodeType": "dbDelete",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — dbDelete",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_dbDelete",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "dbDelete",
            "category": "database",
            "status": "draft",
            "label": "Delete Record",
            "entity": "appointment",
            "recordId": "{{appointment_id}}"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_dbDelete",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_dbDelete",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "dbDelete",
      "end"
    ],
    "expectedOutput": {
      "dbDelete": {
        "success": true,
        "stub": true
      }
    },
    "expectedNextNode": {
      "n_dbDelete": "n_end"
    },
    "expectedFailureBehavior": "Stub currently does not fail"
  }
}
```

---

## Fixture — dbQuery

**File:** `dbQuery.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `dbQuery` — Database Query (database)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — dbQuery",
    "focusNodeType": "dbQuery",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — dbQuery",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_dbQuery",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "dbQuery",
            "category": "database",
            "status": "draft",
            "label": "Database Query",
            "query": "patient.id == context.patient.id"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_dbQuery",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_dbQuery",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "dbQuery",
      "end"
    ],
    "expectedOutput": {
      "dbQuery": {
        "success": true,
        "stub": true
      }
    },
    "expectedNextNode": {
      "n_dbQuery": "n_end"
    },
    "expectedFailureBehavior": "Stub currently does not fail"
  }
}
```

---

## Fixture — dbUpdate

**File:** `dbUpdate.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `dbUpdate` — Update Record (database)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — dbUpdate",
    "focusNodeType": "dbUpdate",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — dbUpdate",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_dbUpdate",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "dbUpdate",
            "category": "database",
            "status": "draft",
            "label": "Update Record",
            "entity": "patient"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_dbUpdate",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_dbUpdate",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "dbUpdate",
      "end"
    ],
    "expectedOutput": {
      "dbUpdate": {
        "success": true,
        "stub": true
      }
    },
    "expectedNextNode": {
      "n_dbUpdate": "n_end"
    },
    "expectedFailureBehavior": "Stub currently does not fail"
  }
}
```

---

## Dentist Segment Campaign

**File:** `dentistSegment.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `condition` — Dentistry? (conditions)
- `sendWhatsApp` — Dentist confirmation (messaging)
- `end` — End (flow)
- `end` — End (not dentistry) (flow)

**JSON**

```json
{
  "meta": {
    "name": "Dentist Segment Campaign",
    "focusNodeType": "appointmentBooked",
    "campaignKey": "dentist_segment",
    "notes": [
      "No DentistCampaignNode. Uses appointmentBooked + department condition + messaging."
    ]
  },
  "workflow": {
    "name": "Dentist Segment Campaign",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "dentist_segment",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "source": [
              "any"
            ],
            "status": "draft"
          }
        },
        {
          "id": "n_cond_dentist",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Dentistry?",
            "name": "Dentistry?",
            "expression": "appointment.department == \"Dentistry\"",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_dentist",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 80
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Dentist confirmation",
            "templateId": "",
            "recipient": "patient",
            "message": "Hi {{patient_name}}, your dental appointment at {{hospital_name}} is confirmed.",
            "campaignStep": "dentist_confirm_1",
            "status": "draft"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 760,
            "y": 80
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        },
        {
          "id": "n_end_skip",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 260
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End (not dentistry)",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_cond_dentist"
        },
        {
          "id": "e2",
          "source": "n_cond_dentist",
          "target": "n_msg_dentist",
          "sourceHandle": "true"
        },
        {
          "id": "e3",
          "source": "n_cond_dentist",
          "target": "n_end_skip",
          "sourceHandle": "false"
        },
        {
          "id": "e4",
          "source": "n_msg_dentist",
          "target": "n_end"
        }
      ]
    }
  }
}
```

---

## Digital Prescription Share

**File:** `digitalPrescriptionCampaign.workflow.json`

**Triggers**

- `prescriptionAdded`

**Nodes**

- `prescriptionAdded` — Prescription Added (triggers)
- `sendWhatsApp` — Share digital prescription (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Digital Prescription Share",
    "focusNodeType": "prescriptionAdded",
    "campaignKey": "digital_prescription",
    "notes": [
      "Manager ‘digital prescription’ maps to prescriptionAdded (alias digitalPrescription).",
      "No separate digitalPrescription node type."
    ]
  },
  "workflow": {
    "name": "Digital Prescription Share",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "prescriptionAdded",
    "module": "prescriptions",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "digital_prescription",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "prescriptionAdded",
            "category": "triggers",
            "label": "Prescription Added",
            "source": [
              "any"
            ],
            "status": "draft"
          }
        },
        {
          "id": "n_msg",
          "type": "workflow",
          "position": {
            "x": 320,
            "y": 160
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Share digital prescription",
            "templateId": "",
            "recipient": "patient",
            "message": "Hi {{patient_name}}, your digital prescription from {{doctor_name}} is ready. View/order: {{pharmacy_link}}",
            "campaignStep": "digital_rx_1",
            "status": "draft"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 600,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_msg"
        },
        {
          "id": "e2",
          "source": "n_msg",
          "target": "n_end"
        }
      ]
    }
  }
}
```

---

## Fixture — end

**File:** `end.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — end",
    "focusNodeType": "end",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — end",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "end"
    ],
    "expectedOutput": {
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        },
        "executionStatus": "completed"
      }
    },
    "expectedNextNode": {
      "n_appointmentBooked": "n_end",
      "n_end": null
    },
    "expectedFailureBehavior": "None — end terminates branch"
  }
}
```

---

## Fixture — familyPackageTierUpdated

**File:** `familyPackageTierUpdated.workflow.json`

**Triggers**

- `appointmentBooked`
- `familyPackageTierUpdated`

**Nodes**

- `familyPackageTierUpdated` — Family Package Plan Tier Updated (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — familyPackageTierUpdated",
    "focusNodeType": "familyPackageTierUpdated",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — familyPackageTierUpdated",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_familyPackageTierUpdated",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "familyPackageTierUpdated",
            "category": "triggers",
            "label": "Family Package Plan Tier Updated",
            "status": "draft",
            "updateType": "any",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_familyPackageTierUpdated",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "familyPackageTierUpdated"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "familyPackageTierUpdated trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "familyPackageTierUpdated"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_familyPackageTierUpdated": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## First Appointment Nurturing

**File:** `firstAppointmentNurturing.workflow.json`

**Triggers**

- `patientRegistered`

**Nodes**

- `patientRegistered` — Patient Registered (triggers)
- `wait` — Wait 7 days (wait)
- `condition` — No appointment yet (conditions)
- `sendWhatsApp` — Nurture step 1 (messaging)
- `wait` — Wait 2 days (wait)
- `condition` — Still no appointment (conditions)
- `sendSms` — Nurture step 2 (messaging)
- `wait` — Wait 24 hours (wait)
- `condition` — Appointment still missing (conditions)
- `sendPush` — Nurture step 3 (messaging)
- `wait` — Wait 2 hours (wait)
- `condition` — Final appointment check (conditions)
- `sendWhatsApp` — Nurture step 4 (messaging)
- `end` — End (flow)
- `end` — End (booked) (flow)

**JSON**

```json
{
  "meta": {
    "name": "First Appointment Nurturing",
    "focusNodeType": "patientRegistered",
    "campaignKey": "first_appointment_nurturing",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Reusable patientRegistered → wait → appointment.exists condition → message campaign.",
      "configuration.campaignKey enables cancel-on-appointmentBooked without hardcoding workflow id.",
      "Each messaging step re-evaluates appointment.exists before send.",
      "Waits use existing duration mode (7 days, 2 days, 24 hours, 2 hours)."
    ]
  },
  "workflow": {
    "name": "First Appointment Nurturing",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "patientRegistered",
    "module": "patients",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "first_appointment_nurturing",
      "suppressOnAppointment": true,
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_patientRegistered",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 200
          },
          "data": {
            "nodeType": "patientRegistered",
            "category": "triggers",
            "label": "Patient Registered",
            "status": "draft",
            "registrationSource": [
              "any"
            ],
            "patientType": "any"
          }
        },
        {
          "id": "n_wait_7d",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 200
          },
          "data": {
            "nodeType": "wait",
            "category": "wait",
            "label": "Wait 7 days",
            "waitType": "duration",
            "amount": 7,
            "unit": "days",
            "status": "draft"
          }
        },
        {
          "id": "n_cond_1",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 200
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "No appointment yet",
            "name": "No appointment yet",
            "expression": "appointment.exists == false",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_1",
          "type": "workflow",
          "position": {
            "x": 760,
            "y": 120
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Nurture step 1",
            "templateId": "",
            "recipient": "patient",
            "message": "Hello {{patient_name}},\n\nYou have not booked your first appointment with {{hospital_name}} yet.\n\nPlease book here:\n{{booking_link}}\n\nFor assistance, contact {{hospital_phone}}.",
            "campaignStep": "nurture_1",
            "status": "draft"
          }
        },
        {
          "id": "n_wait_2d",
          "type": "workflow",
          "position": {
            "x": 1000,
            "y": 120
          },
          "data": {
            "nodeType": "wait",
            "category": "wait",
            "label": "Wait 2 days",
            "waitType": "duration",
            "amount": 2,
            "unit": "days",
            "status": "draft"
          }
        },
        {
          "id": "n_cond_2",
          "type": "workflow",
          "position": {
            "x": 1240,
            "y": 120
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Still no appointment",
            "name": "Still no appointment",
            "expression": "appointment.exists == false",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_2",
          "type": "workflow",
          "position": {
            "x": 1480,
            "y": 40
          },
          "data": {
            "nodeType": "sendSms",
            "category": "messaging",
            "label": "Nurture step 2",
            "templateId": "",
            "recipient": "patient",
            "message": "Hi {{patient_name}}, reminder to book your first visit at {{hospital_name}}: {{booking_link}}",
            "campaignStep": "nurture_2",
            "status": "draft"
          }
        },
        {
          "id": "n_wait_24h",
          "type": "workflow",
          "position": {
            "x": 1720,
            "y": 40
          },
          "data": {
            "nodeType": "wait",
            "category": "wait",
            "label": "Wait 24 hours",
            "waitType": "duration",
            "amount": 24,
            "unit": "hours",
            "status": "draft"
          }
        },
        {
          "id": "n_cond_3",
          "type": "workflow",
          "position": {
            "x": 1960,
            "y": 40
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Appointment still missing",
            "name": "Appointment still missing",
            "expression": "appointment.exists == false",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_3",
          "type": "workflow",
          "position": {
            "x": 2200,
            "y": 0
          },
          "data": {
            "nodeType": "sendPush",
            "category": "messaging",
            "label": "Nurture step 3",
            "templateId": "",
            "recipient": "patient",
            "title": "Book your first appointment",
            "body": "{{patient_name}}, book with {{hospital_name}}: {{booking_link}}",
            "priority": "normal",
            "campaignStep": "nurture_3",
            "status": "draft"
          }
        },
        {
          "id": "n_wait_2h",
          "type": "workflow",
          "position": {
            "x": 2440,
            "y": 0
          },
          "data": {
            "nodeType": "wait",
            "category": "wait",
            "label": "Wait 2 hours",
            "waitType": "duration",
            "amount": 2,
            "unit": "hours",
            "status": "draft"
          }
        },
        {
          "id": "n_cond_4",
          "type": "workflow",
          "position": {
            "x": 2680,
            "y": 0
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Final appointment check",
            "name": "Final appointment check",
            "expression": "appointment.exists == false",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_4",
          "type": "workflow",
          "position": {
            "x": 2920,
            "y": 0
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Nurture step 4",
            "templateId": "",
            "recipient": "patient",
            "message": "Final reminder from {{hospital_name}}. Book now: {{booking_link}} ({{hospital_phone}})",
            "campaignStep": "nurture_4",
            "status": "draft"
          }
        },
        {
          "id": "n_end_success",
          "type": "workflow",
          "position": {
            "x": 3160,
            "y": 0
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        },
        {
          "id": "n_end_booked",
          "type": "workflow",
          "position": {
            "x": 760,
            "y": 320
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End (booked)",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_patientRegistered",
          "target": "n_wait_7d"
        },
        {
          "id": "e2",
          "source": "n_wait_7d",
          "target": "n_cond_1"
        },
        {
          "id": "e3",
          "source": "n_cond_1",
          "target": "n_msg_1",
          "sourceHandle": "true"
        },
        {
          "id": "e4",
          "source": "n_cond_1",
          "target": "n_end_booked",
          "sourceHandle": "false"
        },
        {
          "id": "e5",
          "source": "n_msg_1",
          "target": "n_wait_2d"
        },
        {
          "id": "e6",
          "source": "n_wait_2d",
          "target": "n_cond_2"
        },
        {
          "id": "e7",
          "source": "n_cond_2",
          "target": "n_msg_2",
          "sourceHandle": "true"
        },
        {
          "id": "e8",
          "source": "n_cond_2",
          "target": "n_end_booked",
          "sourceHandle": "false"
        },
        {
          "id": "e9",
          "source": "n_msg_2",
          "target": "n_wait_24h"
        },
        {
          "id": "e10",
          "source": "n_wait_24h",
          "target": "n_cond_3"
        },
        {
          "id": "e11",
          "source": "n_cond_3",
          "target": "n_msg_3",
          "sourceHandle": "true"
        },
        {
          "id": "e12",
          "source": "n_cond_3",
          "target": "n_end_booked",
          "sourceHandle": "false"
        },
        {
          "id": "e13",
          "source": "n_msg_3",
          "target": "n_wait_2h"
        },
        {
          "id": "e14",
          "source": "n_wait_2h",
          "target": "n_cond_4"
        },
        {
          "id": "e15",
          "source": "n_cond_4",
          "target": "n_msg_4",
          "sourceHandle": "true"
        },
        {
          "id": "e16",
          "source": "n_cond_4",
          "target": "n_end_booked",
          "sourceHandle": "false"
        },
        {
          "id": "e17",
          "source": "n_msg_4",
          "target": "n_end_success"
        }
      ]
    }
  },
  "test": {
    "context": {
      "patient": {
        "id": "p_1001",
        "name": "Asha Verma",
        "mobile": "+919876543210",
        "email": "asha@example.com"
      },
      "hospital": {
        "id": 12,
        "name": "Sunrise Hospital",
        "phone": "+911123456789",
        "booking_link": "https://book.sunrise.example/first"
      },
      "appointment": {},
      "trigger": {
        "type": "patientRegistered",
        "triggered_at": "2026-09-05T10:00:00+05:30"
      },
      "triggerPayload": {
        "trigger_type": "patientRegistered"
      }
    }
  }
}
```

---

## Fixture — httpRequest

**File:** `httpRequest.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `httpRequest` — REST API (integrations)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — httpRequest",
    "focusNodeType": "httpRequest",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — httpRequest",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_httpRequest",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "httpRequest",
            "category": "integrations",
            "status": "draft",
            "label": "REST API",
            "url": "https://example.com/api"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_httpRequest",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_httpRequest",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "httpRequest",
      "end"
    ],
    "expectedOutput": {
      "httpRequest": {
        "success": true,
        "stub": true
      }
    },
    "expectedNextNode": {
      "n_httpRequest": "n_end"
    },
    "expectedFailureBehavior": "Stub currently does not fail"
  }
}
```

---

## Inactive Patient 30 Days

**File:** `inactivePatient30.workflow.json`

**Triggers**

- `scheduledEvent`

**Nodes**

- `scheduledEvent` — Daily inactive scan (triggers)
- `condition` — Inactive 30+ days (conditions)
- `sendWhatsApp` — Win-back 30d (messaging)
- `end` — End (flow)
- `end` — End (active) (flow)

**JSON**

```json
{
  "meta": {
    "name": "Inactive Patient 30 Days",
    "focusNodeType": "scheduledEvent",
    "campaignKey": "inactive_30",
    "notes": [
      "Phase 1 approach: daily scheduledEvent + last_visit >= 30 condition.",
      "Laravel cohort loader should inject patient.last_visit_days before condition."
    ]
  },
  "workflow": {
    "name": "Inactive Patient 30 Days",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "scheduledEvent",
    "module": "engagement",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "inactive_30",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "scheduledEvent",
            "category": "triggers",
            "label": "Daily inactive scan",
            "scheduleType": "recurring",
            "startDate": "2026-01-01",
            "executionTime": "10:00",
            "repeatFrequency": "daily",
            "endCondition": "never",
            "status": "draft"
          }
        },
        {
          "id": "n_cond",
          "type": "workflow",
          "position": {
            "x": 320,
            "y": 160
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Inactive 30+ days",
            "name": "Inactive 30+ days",
            "expression": "last_visit >= 30",
            "status": "draft"
          }
        },
        {
          "id": "n_msg",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 80
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Win-back 30d",
            "templateId": "",
            "recipient": "patient",
            "message": "Hi {{patient_name}}, we miss you at {{hospital_name}}. Book a visit: {{booking_link}}",
            "campaignStep": "inactive_30_1",
            "status": "draft"
          }
        },
        {
          "id": "n_end_ok",
          "type": "workflow",
          "position": {
            "x": 800,
            "y": 80
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        },
        {
          "id": "n_end_skip",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 280
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End (active)",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_cond"
        },
        {
          "id": "e2",
          "source": "n_cond",
          "target": "n_msg",
          "sourceHandle": "true"
        },
        {
          "id": "e3",
          "source": "n_cond",
          "target": "n_end_skip",
          "sourceHandle": "false"
        },
        {
          "id": "e4",
          "source": "n_msg",
          "target": "n_end_ok"
        }
      ]
    }
  }
}
```

---

## Inactive Patient 90 Days

**File:** `inactivePatient90.workflow.json`

**Triggers**

- `scheduledEvent`

**Nodes**

- `scheduledEvent` — Daily inactive scan (triggers)
- `condition` — Inactive 90+ days (conditions)
- `sendSms` — Win-back 90d (messaging)
- `end` — End (flow)
- `end` — End (active) (flow)

**JSON**

```json
{
  "meta": {
    "name": "Inactive Patient 90 Days",
    "focusNodeType": "scheduledEvent",
    "campaignKey": "inactive_90",
    "notes": [
      "Same pattern as inactive 30 with last_visit >= 90."
    ]
  },
  "workflow": {
    "name": "Inactive Patient 90 Days",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "scheduledEvent",
    "module": "engagement",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "inactive_90",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "scheduledEvent",
            "category": "triggers",
            "label": "Daily inactive scan",
            "scheduleType": "recurring",
            "startDate": "2026-01-01",
            "executionTime": "10:30",
            "repeatFrequency": "daily",
            "endCondition": "never",
            "status": "draft"
          }
        },
        {
          "id": "n_cond",
          "type": "workflow",
          "position": {
            "x": 320,
            "y": 160
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Inactive 90+ days",
            "name": "Inactive 90+ days",
            "expression": "last_visit >= 90",
            "status": "draft"
          }
        },
        {
          "id": "n_msg",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 80
          },
          "data": {
            "nodeType": "sendSms",
            "category": "messaging",
            "label": "Win-back 90d",
            "templateId": "",
            "recipient": "patient",
            "message": "{{patient_name}}, it's been a while since your last visit to {{hospital_name}}. Schedule now: {{booking_link}}",
            "campaignStep": "inactive_90_1",
            "status": "draft"
          }
        },
        {
          "id": "n_end_ok",
          "type": "workflow",
          "position": {
            "x": 800,
            "y": 80
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        },
        {
          "id": "n_end_skip",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 280
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End (active)",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_cond"
        },
        {
          "id": "e2",
          "source": "n_cond",
          "target": "n_msg",
          "sourceHandle": "true"
        },
        {
          "id": "e3",
          "source": "n_cond",
          "target": "n_end_skip",
          "sourceHandle": "false"
        },
        {
          "id": "e4",
          "source": "n_msg",
          "target": "n_end_ok"
        }
      ]
    }
  }
}
```

---

## Fixture — invoiceGenerated

**File:** `invoiceGenerated.workflow.json`

**Triggers**

- `appointmentBooked`
- `invoiceGenerated`

**Nodes**

- `invoiceGenerated` — Invoice Generated (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — invoiceGenerated",
    "focusNodeType": "invoiceGenerated",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — invoiceGenerated",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_invoiceGenerated",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "invoiceGenerated",
            "category": "triggers",
            "label": "Invoice Generated",
            "status": "draft",
            "source": [
              "any"
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_invoiceGenerated",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "invoiceGenerated"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "invoiceGenerated trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "invoiceGenerated"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_invoiceGenerated": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — labReportNotification

**File:** `labReportNotification.workflow.json`

**Triggers**

- `appointmentBooked`
- `labReportNotification`

**Nodes**

- `labReportNotification` — Lab Report Ready (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — labReportNotification",
    "focusNodeType": "labReportNotification",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — labReportNotification",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_labReportNotification",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "labReportNotification",
            "category": "triggers",
            "label": "Lab Report Ready",
            "status": "draft",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_labReportNotification",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "labReportNotification"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "labReportNotification trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "labReportNotification"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_labReportNotification": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — labTestOrdered

**File:** `labTestOrdered.workflow.json`

**Triggers**

- `appointmentBooked`
- `labTestOrdered`

**Nodes**

- `labTestOrdered` — Lab Test Ordered (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — labTestOrdered",
    "focusNodeType": "labTestOrdered",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — labTestOrdered",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_labTestOrdered",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "labTestOrdered",
            "category": "triggers",
            "label": "Lab Test Ordered",
            "status": "draft",
            "source": [
              "any"
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_labTestOrdered",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "labTestOrdered"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "labTestOrdered trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "labTestOrdered"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_labTestOrdered": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — medicineReminder

**File:** `medicineReminder.workflow.json`

**Triggers**

- `appointmentBooked`
- `medicineReminder`

**Nodes**

- `medicineReminder` — Medicine Reminder Due (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — medicineReminder",
    "focusNodeType": "medicineReminder",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — medicineReminder",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_medicineReminder",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "medicineReminder",
            "category": "triggers",
            "label": "Medicine Reminder Due",
            "status": "draft",
            "reminderTiming": "at_due",
            "minutesBefore": 30,
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_medicineReminder",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "medicineReminder"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "medicineReminder trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "medicineReminder"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_medicineReminder": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Medicine Reminder

**File:** `medicineReminderCampaign.workflow.json`

**Triggers**

- `medicineReminder`

**Nodes**

- `medicineReminder` — Medicine Reminder Due (triggers)
- `sendWhatsApp` — Send medicine reminder (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Medicine Reminder Campaign",
    "focusNodeType": "medicineReminder",
    "campaignKey": "medicine_reminder",
    "notes": [
      "Reuses existing medicineReminder trigger — no new node.",
      "Laravel must schedule due times and inject medicine context."
    ]
  },
  "workflow": {
    "name": "Medicine Reminder",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "medicineReminder",
    "module": "prescriptions",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "medicine_reminder",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "medicineReminder",
            "category": "triggers",
            "label": "Medicine Reminder Due",
            "reminderTiming": "at_due",
            "minutesBefore": 30,
            "status": "draft"
          }
        },
        {
          "id": "n_msg",
          "type": "workflow",
          "position": {
            "x": 320,
            "y": 160
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Send medicine reminder",
            "templateId": "",
            "recipient": "patient",
            "message": "Hi {{patient_name}}, reminder to take {{medicine_name}} ({{dosage}}, {{frequency}}).",
            "campaignStep": "medicine_reminder_1",
            "status": "draft"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 600,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_msg"
        },
        {
          "id": "e2",
          "source": "n_msg",
          "target": "n_end"
        }
      ]
    }
  }
}
```

---

## Fixture — membershipExpiry

**File:** `membershipExpiry.workflow.json`

**Triggers**

- `appointmentBooked`
- `membershipExpiry`

**Nodes**

- `membershipExpiry` — Hospital Membership Expiry (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — membershipExpiry",
    "focusNodeType": "membershipExpiry",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — membershipExpiry",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_membershipExpiry",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "membershipExpiry",
            "category": "triggers",
            "label": "Hospital Membership Expiry",
            "status": "draft",
            "triggerTiming": "before_expiry",
            "numberOfDays": 7,
            "executionTime": "09:00",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_membershipExpiry",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "membershipExpiry"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "membershipExpiry trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "membershipExpiry"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_membershipExpiry": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Missed Appointment Restart

**File:** `missedAppointmentRestart.workflow.json`

**Triggers**

- `appointmentMissed`

**Nodes**

- `appointmentMissed` — Appointment Missed (triggers)
- `sendWhatsApp` — Restart step 1 (messaging)
- `wait` — Wait 2 days (wait)
- `condition` — Still no appointment? (conditions)
- `sendSms` — Restart step 2 (messaging)
- `end` — End (flow)
- `end` — End (rebooked) (flow)

**JSON**

```json
{
  "meta": {
    "name": "Missed Appointment Restart",
    "focusNodeType": "appointmentMissed",
    "campaignKey": "missed_appointment_restart",
    "notes": [
      "On missed/no-show, restart recovery messaging with a dedicated campaignKey.",
      "Laravel should cancel conflicting scheduled first-appointment / follow-up steps then start this graph when product policy requires it."
    ]
  },
  "workflow": {
    "name": "Missed Appointment Restart",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentMissed",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "missed_appointment_restart",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentMissed",
            "category": "triggers",
            "label": "Appointment Missed",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_1",
          "type": "workflow",
          "position": {
            "x": 320,
            "y": 160
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Restart step 1",
            "templateId": "",
            "recipient": "patient",
            "message": "Hi {{patient_name}}, we missed you at {{hospital_name}}. Please rebook: {{booking_link}}",
            "campaignStep": "missed_restart_1",
            "status": "draft"
          }
        },
        {
          "id": "n_wait",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "wait",
            "category": "wait",
            "label": "Wait 2 days",
            "waitType": "duration",
            "amount": 2,
            "unit": "days",
            "status": "draft"
          }
        },
        {
          "id": "n_cond",
          "type": "workflow",
          "position": {
            "x": 800,
            "y": 160
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Still no appointment?",
            "name": "Still no appointment?",
            "expression": "appointment.exists == false",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_2",
          "type": "workflow",
          "position": {
            "x": 1040,
            "y": 80
          },
          "data": {
            "nodeType": "sendSms",
            "category": "messaging",
            "label": "Restart step 2",
            "templateId": "",
            "recipient": "patient",
            "message": "{{patient_name}}, please book again with {{hospital_name}}: {{booking_link}}",
            "campaignStep": "missed_restart_2",
            "status": "draft"
          }
        },
        {
          "id": "n_end_ok",
          "type": "workflow",
          "position": {
            "x": 1280,
            "y": 80
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        },
        {
          "id": "n_end_booked",
          "type": "workflow",
          "position": {
            "x": 1040,
            "y": 280
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End (rebooked)",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_msg_1"
        },
        {
          "id": "e2",
          "source": "n_msg_1",
          "target": "n_wait"
        },
        {
          "id": "e3",
          "source": "n_wait",
          "target": "n_cond"
        },
        {
          "id": "e4",
          "source": "n_cond",
          "target": "n_msg_2",
          "sourceHandle": "true"
        },
        {
          "id": "e5",
          "source": "n_cond",
          "target": "n_end_booked",
          "sourceHandle": "false"
        },
        {
          "id": "e6",
          "source": "n_msg_2",
          "target": "n_end_ok"
        }
      ]
    }
  }
}
```

---

## Fixture — onChatMessage

**File:** `onChatMessage.workflow.json`

**Triggers**

- `appointmentBooked`
- `onChatMessage`

**Nodes**

- `onChatMessage` — On Message Received (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — onChatMessage",
    "focusNodeType": "onChatMessage",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — onChatMessage",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_onChatMessage",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "onChatMessage",
            "category": "triggers",
            "label": "On Message Received",
            "status": "draft",
            "channel": [
              "any"
            ],
            "messageType": [
              "any"
            ],
            "messageMatch": "any",
            "tags": [],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_onChatMessage",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "onChatMessage"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "onChatMessage trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "onChatMessage"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_onChatMessage": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Parent–Child Segment Campaign

**File:** `parentChildSegment.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `condition` — Child / minor? (conditions)
- `sendWhatsApp` — Message caregiver (messaging)
- `end` — End (flow)
- `end` — End (not child) (flow)

**JSON**

```json
{
  "meta": {
    "name": "Parent–Child Segment Campaign",
    "focusNodeType": "appointmentBooked",
    "campaignKey": "parent_child_segment",
    "notes": [
      "No ParentChildNode. Uses appointmentBooked + age/relationship condition + caregiver recipient messaging."
    ]
  },
  "workflow": {
    "name": "Parent–Child Segment Campaign",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "parent_child_segment",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "source": [
              "any"
            ],
            "status": "draft"
          }
        },
        {
          "id": "n_cond_child",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Child / minor?",
            "name": "Child / minor?",
            "expression": "patient.age < 18 || patient.relationship == \"child\"",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_parent",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 80
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Message caregiver",
            "templateId": "",
            "recipient": "caregiver",
            "message": "Reminder: {{patient_name}} has an appointment at {{hospital_name}}. Details: {{booking_link}}",
            "campaignStep": "parent_child_1",
            "status": "draft"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 760,
            "y": 80
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        },
        {
          "id": "n_end_skip",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 260
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End (not child)",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_cond_child"
        },
        {
          "id": "e2",
          "source": "n_cond_child",
          "target": "n_msg_parent",
          "sourceHandle": "true"
        },
        {
          "id": "e3",
          "source": "n_cond_child",
          "target": "n_end_skip",
          "sourceHandle": "false"
        },
        {
          "id": "e4",
          "source": "n_msg_parent",
          "target": "n_end"
        }
      ]
    }
  }
}
```

---

## Fixture — patientRegistered

**File:** `patientRegistered.workflow.json`

**Triggers**

- `appointmentBooked`
- `patientRegistered`

**Nodes**

- `patientRegistered` — Patient Registered (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — patientRegistered",
    "focusNodeType": "patientRegistered",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — patientRegistered",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_patientRegistered",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "patientRegistered",
            "category": "triggers",
            "label": "Patient Registered",
            "status": "draft",
            "registrationSource": [
              "any"
            ],
            "patientType": "any",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_patientRegistered",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "patientRegistered"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "patientRegistered trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "patientRegistered"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_patientRegistered": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — paymentReceived

**File:** `paymentReceived.workflow.json`

**Triggers**

- `appointmentBooked`
- `paymentReceived`

**Nodes**

- `paymentReceived` — Payment Received (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — paymentReceived",
    "focusNodeType": "paymentReceived",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — paymentReceived",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_paymentReceived",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "paymentReceived",
            "category": "triggers",
            "label": "Payment Received",
            "status": "draft",
            "paymentMode": [
              "any"
            ],
            "source": [
              "any"
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_paymentReceived",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "paymentReceived"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "paymentReceived trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "paymentReceived"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_paymentReceived": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — pharmacyRefillDue

**File:** `pharmacyRefillDue.workflow.json`

**Triggers**

- `appointmentBooked`
- `pharmacyRefillDue`

**Nodes**

- `pharmacyRefillDue` — Pharmacy Refill Due (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — pharmacyRefillDue",
    "focusNodeType": "pharmacyRefillDue",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — pharmacyRefillDue",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_pharmacyRefillDue",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "pharmacyRefillDue",
            "category": "triggers",
            "label": "Pharmacy Refill Due",
            "status": "draft",
            "daysBeforeRefill": 3,
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_pharmacyRefillDue",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "pharmacyRefillDue"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "pharmacyRefillDue trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "pharmacyRefillDue"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_pharmacyRefillDue": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Post-Visit Follow-up

**File:** `postVisitFollowup.workflow.json`

**Triggers**

- `appointmentCompleted`

**Nodes**

- `appointmentCompleted` — Appointment Completed (triggers)
- `condition` — Follow-up set? (conditions)
- `sendWhatsApp` — Notify doctor (messaging)
- `wait` — Wait until 4 days before follow-up (wait)
- `sendWhatsApp` — Day-4 patient reminder (messaging)
- `wait` — Wait until follow-up day (wait)
- `sendSms` — Follow-up day reminder (messaging)
- `end` — End (flow)
- `end` — End (no follow-up) (flow)

**JSON**

```json
{
  "meta": {
    "name": "Post-Visit Follow-up",
    "focusNodeType": "appointmentCompleted",
    "campaignKey": "post_visit_followup",
    "notes": [
      "Uses followup.exists condition and wait relative_date (day-4 before + on follow-up date).",
      "Doctor notified when follow-up is set; patient reminded on day-4 and last day."
    ]
  },
  "workflow": {
    "name": "Post-Visit Follow-up",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentCompleted",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "post_visit_followup",
      "suppressOnAppointment": false,
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 200
          },
          "data": {
            "nodeType": "appointmentCompleted",
            "category": "triggers",
            "label": "Appointment Completed",
            "source": [
              "any"
            ],
            "requireFollowUp": "yes",
            "status": "draft"
          }
        },
        {
          "id": "n_cond_fu",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 200
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Follow-up set?",
            "name": "Follow-up set?",
            "expression": "followup.exists == true",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_doctor",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 80
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Notify doctor",
            "templateId": "",
            "recipient": "doctor",
            "message": "Dr {{doctor_name}}: follow-up for {{patient_name}} is set for {{followup_date}}.",
            "campaignStep": "followup_doctor_notice",
            "status": "draft"
          }
        },
        {
          "id": "n_wait_day4",
          "type": "workflow",
          "position": {
            "x": 760,
            "y": 80
          },
          "data": {
            "nodeType": "wait",
            "category": "wait",
            "label": "Wait until 4 days before follow-up",
            "waitType": "relative_date",
            "relativeDateField": "followup.date",
            "relativeOffsetDirection": "before",
            "relativeOffsetAmount": 4,
            "relativeOffsetUnit": "days",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_day4",
          "type": "workflow",
          "position": {
            "x": 1000,
            "y": 80
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Day-4 patient reminder",
            "templateId": "",
            "recipient": "patient",
            "message": "Hi {{patient_name}}, your follow-up with {{doctor_name}} is on {{followup_date}}. Please confirm your visit.",
            "campaignStep": "followup_day4",
            "status": "draft"
          }
        },
        {
          "id": "n_wait_lastday",
          "type": "workflow",
          "position": {
            "x": 1240,
            "y": 80
          },
          "data": {
            "nodeType": "wait",
            "category": "wait",
            "label": "Wait until follow-up day",
            "waitType": "relative_date",
            "relativeDateField": "followup.date",
            "relativeOffsetDirection": "on",
            "relativeOffsetAmount": 0,
            "relativeOffsetUnit": "days",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_lastday",
          "type": "workflow",
          "position": {
            "x": 1480,
            "y": 80
          },
          "data": {
            "nodeType": "sendSms",
            "category": "messaging",
            "label": "Follow-up day reminder",
            "templateId": "",
            "recipient": "patient",
            "message": "Hi {{patient_name}}, today is your follow-up at {{hospital_name}} ({{followup_date}}).",
            "campaignStep": "followup_lastday",
            "status": "draft"
          }
        },
        {
          "id": "n_end_ok",
          "type": "workflow",
          "position": {
            "x": 1720,
            "y": 80
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        },
        {
          "id": "n_end_nofu",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 320
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End (no follow-up)",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_cond_fu"
        },
        {
          "id": "e2",
          "source": "n_cond_fu",
          "target": "n_msg_doctor",
          "sourceHandle": "true"
        },
        {
          "id": "e3",
          "source": "n_cond_fu",
          "target": "n_end_nofu",
          "sourceHandle": "false"
        },
        {
          "id": "e4",
          "source": "n_msg_doctor",
          "target": "n_wait_day4"
        },
        {
          "id": "e5",
          "source": "n_wait_day4",
          "target": "n_msg_day4"
        },
        {
          "id": "e6",
          "source": "n_msg_day4",
          "target": "n_wait_lastday"
        },
        {
          "id": "e7",
          "source": "n_wait_lastday",
          "target": "n_msg_lastday"
        },
        {
          "id": "e8",
          "source": "n_msg_lastday",
          "target": "n_end_ok"
        }
      ]
    }
  }
}
```

---

## Fixture — prescriptionAdded

**File:** `prescriptionAdded.workflow.json`

**Triggers**

- `appointmentBooked`
- `prescriptionAdded`

**Nodes**

- `prescriptionAdded` — Prescription Added (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — prescriptionAdded",
    "focusNodeType": "prescriptionAdded",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — prescriptionAdded",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_prescriptionAdded",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "prescriptionAdded",
            "category": "triggers",
            "label": "Prescription Added",
            "status": "draft",
            "source": [
              "any"
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_prescriptionAdded",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "prescriptionAdded"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "prescriptionAdded trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "prescriptionAdded"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_prescriptionAdded": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — rewardUpdated

**File:** `rewardUpdated.workflow.json`

**Triggers**

- `appointmentBooked`
- `rewardUpdated`

**Nodes**

- `rewardUpdated` — Reward Points Updated (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — rewardUpdated",
    "focusNodeType": "rewardUpdated",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — rewardUpdated",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_rewardUpdated",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "rewardUpdated",
            "category": "triggers",
            "label": "Reward Points Updated",
            "status": "draft",
            "updateType": [
              "any"
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_rewardUpdated",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "rewardUpdated"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "rewardUpdated trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "rewardUpdated"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_rewardUpdated": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — rewardsTierUpgraded

**File:** `rewardsTierUpgraded.workflow.json`

**Triggers**

- `appointmentBooked`
- `rewardsTierUpgraded`

**Nodes**

- `rewardsTierUpgraded` — Rewards Tier Upgraded (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — rewardsTierUpgraded",
    "focusNodeType": "rewardsTierUpgraded",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — rewardsTierUpgraded",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_rewardsTierUpgraded",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "rewardsTierUpgraded",
            "category": "triggers",
            "label": "Rewards Tier Upgraded",
            "status": "draft",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_rewardsTierUpgraded",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "rewardsTierUpgraded"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "rewardsTierUpgraded trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "rewardsTierUpgraded"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_rewardsTierUpgraded": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — scheduledEvent

**File:** `scheduledEvent.workflow.json`

**Triggers**

- `appointmentBooked`
- `scheduledEvent`

**Nodes**

- `scheduledEvent` — Scheduled Event (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — scheduledEvent",
    "focusNodeType": "scheduledEvent",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — scheduledEvent",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_scheduledEvent",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "scheduledEvent",
            "category": "triggers",
            "label": "Scheduled Event",
            "status": "draft",
            "scheduleType": "once",
            "startDate": "",
            "executionTime": "09:00",
            "repeatFrequency": "daily",
            "endCondition": "never",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_scheduledEvent",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "scheduledEvent"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "scheduledEvent trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "scheduledEvent"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_scheduledEvent": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Segment Variants Example

**File:** `segmentDentistSeniorParentChild.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `condition` — Dentistry? (conditions)
- `sendWhatsApp` — Dentist confirmation (messaging)
- `condition` — Senior (60+)? (conditions)
- `sendSms` — Senior-friendly reminder (messaging)
- `condition` — Child / minor? (conditions)
- `sendWhatsApp` — Message caregiver (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Segment variants — Dentist / Senior / Parent-Child",
    "focusNodeType": "appointmentBooked",
    "campaignKey": "segment_variants",
    "notes": [
      "No specialty nodes. Same trigger with condition branches for dentist, senior (age>=60), and parent/child messaging.",
      "Duplicate this pattern per channel/template as needed."
    ]
  },
  "workflow": {
    "name": "Segment Variants Example",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "segment_variants",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 240
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "source": [
              "any"
            ],
            "status": "draft"
          }
        },
        {
          "id": "n_cond_dentist",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 40
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Dentistry?",
            "name": "Dentistry?",
            "expression": "appointment.department == \"Dentistry\"",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_dentist",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 0
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Dentist confirmation",
            "templateId": "",
            "recipient": "patient",
            "message": "Hi {{patient_name}}, your dental appointment at {{hospital_name}} is confirmed.",
            "campaignStep": "segment_dentist",
            "status": "draft"
          }
        },
        {
          "id": "n_cond_senior",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 220
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Senior (60+)?",
            "name": "Senior (60+)?",
            "expression": "patient.age >= 60",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_senior",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 180
          },
          "data": {
            "nodeType": "sendSms",
            "category": "messaging",
            "label": "Senior-friendly reminder",
            "templateId": "",
            "recipient": "patient",
            "message": "Dear {{patient_name}}, your visit is confirmed. Call {{hospital_phone}} if you need assistance.",
            "campaignStep": "segment_senior",
            "status": "draft"
          }
        },
        {
          "id": "n_cond_child",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 400
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Child / minor?",
            "name": "Child / minor?",
            "expression": "patient.age < 18 || patient.relationship == \"child\"",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_parent",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 360
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Message caregiver",
            "templateId": "",
            "recipient": "caregiver",
            "message": "Reminder: {{patient_name}} has an appointment at {{hospital_name}}. Details: {{booking_link}}",
            "campaignStep": "segment_parent_child",
            "status": "draft"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 800,
            "y": 240
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_cond_dentist"
        },
        {
          "id": "e2",
          "source": "n_cond_dentist",
          "target": "n_msg_dentist",
          "sourceHandle": "true"
        },
        {
          "id": "e3",
          "source": "n_cond_dentist",
          "target": "n_cond_senior",
          "sourceHandle": "false"
        },
        {
          "id": "e4",
          "source": "n_msg_dentist",
          "target": "n_end"
        },
        {
          "id": "e5",
          "source": "n_cond_senior",
          "target": "n_msg_senior",
          "sourceHandle": "true"
        },
        {
          "id": "e6",
          "source": "n_cond_senior",
          "target": "n_cond_child",
          "sourceHandle": "false"
        },
        {
          "id": "e7",
          "source": "n_msg_senior",
          "target": "n_end"
        },
        {
          "id": "e8",
          "source": "n_cond_child",
          "target": "n_msg_parent",
          "sourceHandle": "true"
        },
        {
          "id": "e9",
          "source": "n_cond_child",
          "target": "n_end",
          "sourceHandle": "false"
        },
        {
          "id": "e10",
          "source": "n_msg_parent",
          "target": "n_end"
        }
      ]
    }
  }
}
```

---

## Fixture — sendAiChat

**File:** `sendAiChat.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `sendAiChat` — Send AI Chat (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — sendAiChat",
    "focusNodeType": "sendAiChat",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — sendAiChat",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_sendAiChat",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "sendAiChat",
            "category": "messaging",
            "label": "Send AI Chat",
            "templateId": "tpl_example",
            "recipient": "patient",
            "repeatReminder": false,
            "retryInterval": 15,
            "maxRetryCount": 2,
            "fallbackChannel": "",
            "variables": {},
            "status": "draft",
            "prompt": "You are a helpful hospital care assistant.",
            "temperature": 0.7
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_sendAiChat",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_sendAiChat",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "sendAiChat",
      "end"
    ],
    "expectedOutput": {
      "sendAiChat": {
        "success": true,
        "channel": "ai_chat",
        "currentImplementation": "ChannelManager.send result (JS adapters stub messageId)"
      }
    },
    "expectedNextNode": {
      "n_sendAiChat": "n_end"
    },
    "expectedFailureBehavior": "dispatch failure → action=error; WorkflowExecutor fails execution"
  }
}
```

---

## Fixture — sendAiVoice

**File:** `sendAiVoice.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `sendAiVoice` — Send AI Voice Call (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — sendAiVoice",
    "focusNodeType": "sendAiVoice",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — sendAiVoice",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_sendAiVoice",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "sendAiVoice",
            "category": "messaging",
            "label": "Send AI Voice Call",
            "templateId": "tpl_example",
            "recipient": "patient",
            "repeatReminder": false,
            "retryInterval": 15,
            "maxRetryCount": 2,
            "fallbackChannel": "",
            "variables": {},
            "status": "draft",
            "voiceProvider": "default",
            "prompt": "Remind {{patient_name}} about the appointment.",
            "language": "en",
            "voice": "",
            "gender": "neutral",
            "retryCount": 1
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_sendAiVoice",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_sendAiVoice",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "sendAiVoice",
      "end"
    ],
    "expectedOutput": {
      "sendAiVoice": {
        "success": true,
        "channel": "ai_voice",
        "currentImplementation": "ChannelManager.send result (JS adapters stub messageId)"
      }
    },
    "expectedNextNode": {
      "n_sendAiVoice": "n_end"
    },
    "expectedFailureBehavior": "dispatch failure → action=error; WorkflowExecutor fails execution"
  }
}
```

---

## Fixture — sendEmail

**File:** `sendEmail.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `sendEmail` — Send Email (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — sendEmail",
    "focusNodeType": "sendEmail",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — sendEmail",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_sendEmail",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "sendEmail",
            "category": "messaging",
            "label": "Send Email",
            "templateId": "tpl_example",
            "recipient": "patient",
            "repeatReminder": false,
            "retryInterval": 15,
            "maxRetryCount": 2,
            "fallbackChannel": "",
            "variables": {},
            "status": "draft",
            "subject": "Appointment — {{hospital_name}}",
            "body": "Dear {{patient_name}}, see you on {{appointment_date}}."
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_sendEmail",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_sendEmail",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "sendEmail",
      "end"
    ],
    "expectedOutput": {
      "sendEmail": {
        "success": true,
        "channel": "email",
        "currentImplementation": "ChannelManager.send result (JS adapters stub messageId)"
      }
    },
    "expectedNextNode": {
      "n_sendEmail": "n_end"
    },
    "expectedFailureBehavior": "dispatch failure → action=error; WorkflowExecutor fails execution"
  }
}
```

---

## Fixture — sendIvr

**File:** `sendIvr.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `sendIvr` — Send IVR (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — sendIvr",
    "focusNodeType": "sendIvr",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — sendIvr",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_sendIvr",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "sendIvr",
            "category": "messaging",
            "label": "Send IVR",
            "templateId": "tpl_example",
            "recipient": "patient",
            "repeatReminder": false,
            "retryInterval": 15,
            "maxRetryCount": 2,
            "fallbackChannel": "",
            "variables": {},
            "status": "draft"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_sendIvr",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_sendIvr",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "sendIvr",
      "end"
    ],
    "expectedOutput": {
      "sendIvr": {
        "success": true,
        "channel": "ivr",
        "currentImplementation": "ChannelManager.send result (JS adapters stub messageId)"
      }
    },
    "expectedNextNode": {
      "n_sendIvr": "n_end"
    },
    "expectedFailureBehavior": "dispatch failure → action=error; WorkflowExecutor fails execution"
  }
}
```

---

## Fixture — sendPush

**File:** `sendPush.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `sendPush` — Send Push Notification (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — sendPush",
    "focusNodeType": "sendPush",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — sendPush",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_sendPush",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "sendPush",
            "category": "messaging",
            "label": "Send Push Notification",
            "templateId": "tpl_example",
            "recipient": "patient",
            "repeatReminder": false,
            "retryInterval": 15,
            "maxRetryCount": 2,
            "fallbackChannel": "",
            "variables": {},
            "status": "draft",
            "title": "Appointment booked",
            "body": "Hi {{patient_name}}",
            "priority": "normal"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_sendPush",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_sendPush",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "sendPush",
      "end"
    ],
    "expectedOutput": {
      "sendPush": {
        "success": true,
        "channel": "push",
        "currentImplementation": "ChannelManager.send result (JS adapters stub messageId)"
      }
    },
    "expectedNextNode": {
      "n_sendPush": "n_end"
    },
    "expectedFailureBehavior": "dispatch failure → action=error; WorkflowExecutor fails execution"
  }
}
```

---

## Fixture — sendSms

**File:** `sendSms.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `sendSms` — Send SMS (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — sendSms",
    "focusNodeType": "sendSms",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — sendSms",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_sendSms",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "sendSms",
            "category": "messaging",
            "label": "Send SMS",
            "templateId": "tpl_example",
            "recipient": "patient",
            "repeatReminder": false,
            "retryInterval": 15,
            "maxRetryCount": 2,
            "fallbackChannel": "",
            "variables": {},
            "status": "draft",
            "message": "Hi {{patient_name}}, reminder from {{hospital_name}}."
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_sendSms",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_sendSms",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "sendSms",
      "end"
    ],
    "expectedOutput": {
      "sendSms": {
        "success": true,
        "channel": "sms",
        "currentImplementation": "ChannelManager.send result (JS adapters stub messageId)"
      }
    },
    "expectedNextNode": {
      "n_sendSms": "n_end"
    },
    "expectedFailureBehavior": "dispatch failure → action=error; WorkflowExecutor fails execution"
  }
}
```

---

## Fixture — sendTemplate

**File:** `sendTemplate.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `sendTemplate` — Send Template (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — sendTemplate",
    "focusNodeType": "sendTemplate",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — sendTemplate",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_sendTemplate",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "sendTemplate",
            "category": "messaging",
            "label": "Send Template",
            "templateId": "tpl_example",
            "recipient": "patient",
            "repeatReminder": false,
            "retryInterval": 15,
            "maxRetryCount": 2,
            "fallbackChannel": "",
            "variables": {},
            "status": "draft",
            "channel": "whatsapp"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_sendTemplate",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_sendTemplate",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "sendTemplate",
      "end"
    ],
    "expectedOutput": {
      "sendTemplate": {
        "success": true,
        "channel": "multi",
        "currentImplementation": "ChannelManager.send result (JS adapters stub messageId)"
      }
    },
    "expectedNextNode": {
      "n_sendTemplate": "n_end"
    },
    "expectedFailureBehavior": "dispatch failure → action=error; WorkflowExecutor fails execution"
  }
}
```

---

## Fixture — sendWhatsApp

**File:** `sendWhatsApp.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `sendWhatsApp` — Send WhatsApp (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — sendWhatsApp",
    "focusNodeType": "sendWhatsApp",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — sendWhatsApp",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_sendWhatsApp",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Send WhatsApp",
            "templateId": "tpl_example",
            "recipient": "patient",
            "repeatReminder": false,
            "retryInterval": 15,
            "maxRetryCount": 2,
            "fallbackChannel": "sms",
            "variables": {},
            "status": "draft",
            "message": "Hi {{patient_name}}, appointment with {{doctor_name}} on {{appointment_date}} at {{appointment_time}}.",
            "buttons": ""
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_sendWhatsApp",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_sendWhatsApp",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "sendWhatsApp",
      "end"
    ],
    "expectedOutput": {
      "sendWhatsApp": {
        "success": true,
        "channel": "whatsapp",
        "currentImplementation": "ChannelManager.send result (JS adapters stub messageId)"
      }
    },
    "expectedNextNode": {
      "n_sendWhatsApp": "n_end"
    },
    "expectedFailureBehavior": "dispatch failure → action=error; WorkflowExecutor fails execution"
  }
}
```

---

## Senior Patient Segment Campaign

**File:** `seniorPatientSegment.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `condition` — Senior (60+)? (conditions)
- `sendSms` — Senior-friendly reminder (messaging)
- `end` — End (flow)
- `end` — End (not senior) (flow)

**JSON**

```json
{
  "meta": {
    "name": "Senior Patient Segment Campaign",
    "focusNodeType": "appointmentBooked",
    "campaignKey": "senior_patient_segment",
    "notes": [
      "No SeniorCitizenNode. Uses appointmentBooked + patient.age condition + messaging."
    ]
  },
  "workflow": {
    "name": "Senior Patient Segment Campaign",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "senior_patient_segment",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "source": [
              "any"
            ],
            "status": "draft"
          }
        },
        {
          "id": "n_cond_senior",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Senior (60+)?",
            "name": "Senior (60+)?",
            "expression": "patient.age >= 60",
            "status": "draft"
          }
        },
        {
          "id": "n_msg_senior",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 80
          },
          "data": {
            "nodeType": "sendSms",
            "category": "messaging",
            "label": "Senior-friendly reminder",
            "templateId": "",
            "recipient": "patient",
            "message": "Dear {{patient_name}}, your visit is confirmed. Call {{hospital_phone}} if you need assistance.",
            "campaignStep": "senior_confirm_1",
            "status": "draft"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 760,
            "y": 80
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        },
        {
          "id": "n_end_skip",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 260
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End (not senior)",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_cond_senior"
        },
        {
          "id": "e2",
          "source": "n_cond_senior",
          "target": "n_msg_senior",
          "sourceHandle": "true"
        },
        {
          "id": "e3",
          "source": "n_cond_senior",
          "target": "n_end_skip",
          "sourceHandle": "false"
        },
        {
          "id": "e4",
          "source": "n_msg_senior",
          "target": "n_end"
        }
      ]
    }
  }
}
```

---

## Fixture — start

**File:** `start.workflow.json`

**Triggers**

- `appointmentBooked`
- `start`

**Nodes**

- `start` — Workflow Start (triggers)
- `appointmentBooked` — Appointment Booked (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — start",
    "focusNodeType": "start",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — start",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_start",
          "type": "workflow",
          "position": {
            "x": -200,
            "y": 160
          },
          "data": {
            "nodeType": "start",
            "category": "triggers",
            "label": "Workflow Start",
            "status": "ready",
            "triggerSource": "manual"
          }
        },
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_start",
          "target": "n_appointmentBooked",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_appointmentBooked",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "start (passthrough)",
      "appointmentBooked (continue)",
      "end"
    ],
    "expectedOutput": {
      "start": {
        "action": "continue"
      },
      "end": {
        "action": "end",
        "outcome": "completed"
      }
    },
    "expectedNextNode": {
      "n_start": "n_appointmentBooked",
      "n_appointmentBooked": "n_end"
    },
    "expectedFailureBehavior": "Missing outgoing edge throws",
    "note": "start is stripped before Laravel persistence; production graphs begin at trigger."
  }
}
```

---

## Fixture — updateAppointment

**File:** `updateAppointment.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `updateAppointment` — Update Appointment (database)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — updateAppointment",
    "focusNodeType": "updateAppointment",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — updateAppointment",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_updateAppointment",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "updateAppointment",
            "category": "database",
            "status": "draft",
            "label": "Update Appointment"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_updateAppointment",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_updateAppointment",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "updateAppointment",
      "end"
    ],
    "expectedOutput": {
      "updateAppointment": {
        "success": true,
        "stub": true
      }
    },
    "expectedNextNode": {
      "n_updateAppointment": "n_end"
    },
    "expectedFailureBehavior": "Stub currently does not fail"
  }
}
```

---

## Fixture — updateMembership

**File:** `updateMembership.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `updateMembership` — Update Membership (database)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — updateMembership",
    "focusNodeType": "updateMembership",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — updateMembership",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_updateMembership",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "updateMembership",
            "category": "database",
            "status": "draft",
            "label": "Update Membership"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_updateMembership",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_updateMembership",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "updateMembership",
      "end"
    ],
    "expectedOutput": {
      "updateMembership": {
        "success": true,
        "stub": true
      }
    },
    "expectedNextNode": {
      "n_updateMembership": "n_end"
    },
    "expectedFailureBehavior": "Stub currently does not fail"
  }
}
```

---

## Fixture — updatePrescription

**File:** `updatePrescription.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `updatePrescription` — Update Prescription (database)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — updatePrescription",
    "focusNodeType": "updatePrescription",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — updatePrescription",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_updatePrescription",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "updatePrescription",
            "category": "database",
            "status": "draft",
            "label": "Update Prescription"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_updatePrescription",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_updatePrescription",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "updatePrescription",
      "end"
    ],
    "expectedOutput": {
      "updatePrescription": {
        "success": true,
        "stub": true
      }
    },
    "expectedNextNode": {
      "n_updatePrescription": "n_end"
    },
    "expectedFailureBehavior": "Stub currently does not fail"
  }
}
```

---

## Fixture — userPlanExpiry

**File:** `userPlanExpiry.workflow.json`

**Triggers**

- `appointmentBooked`
- `userPlanExpiry`

**Nodes**

- `userPlanExpiry` — User Plan Expiry (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — userPlanExpiry",
    "focusNodeType": "userPlanExpiry",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — userPlanExpiry",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_userPlanExpiry",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "userPlanExpiry",
            "category": "triggers",
            "label": "User Plan Expiry",
            "status": "draft",
            "triggerTiming": "before_expiry",
            "numberOfDays": 7,
            "executionTime": "09:00",
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_userPlanExpiry",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "userPlanExpiry"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "userPlanExpiry trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "userPlanExpiry"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_userPlanExpiry": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Fixture — wait

**File:** `wait.workflow.json`

**Triggers**

- `appointmentBooked`

**Nodes**

- `appointmentBooked` — Appointment Booked (triggers)
- `wait` — Wait (wait)
- `sendPush` — Send Push Notification (messaging)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — wait",
    "focusNodeType": "wait",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — wait",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_appointmentBooked",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "appointmentBooked",
            "category": "triggers",
            "label": "Appointment Booked",
            "status": "draft",
            "source": [
              "any"
            ]
          }
        },
        {
          "id": "n_wait",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "wait",
            "category": "wait",
            "label": "Wait",
            "waitType": "duration",
            "amount": 30,
            "unit": "minutes",
            "status": "draft"
          }
        },
        {
          "id": "n_sendPush",
          "type": "workflow",
          "position": {
            "x": 480,
            "y": 160
          },
          "data": {
            "nodeType": "sendPush",
            "category": "messaging",
            "label": "Send Push Notification",
            "templateId": "",
            "recipient": "patient",
            "title": "Follow-up",
            "body": "Hi {{patient_name}}",
            "priority": "normal",
            "status": "draft"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 700,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_appointmentBooked",
          "target": "n_wait",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e2",
          "source": "n_wait",
          "target": "n_sendPush",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        },
        {
          "id": "e3",
          "source": "n_sendPush",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "source": "mobile"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "appointmentBooked",
      "wait delay 30m",
      "resume → sendPush",
      "end"
    ],
    "expectedOutput": {
      "wait": {
        "action": "delay",
        "delayMs": 1800000
      },
      "sendPush": {
        "success": true,
        "channel": "push"
      }
    },
    "expectedNextNode": {
      "n_wait": "n_sendPush",
      "n_sendPush": "n_end"
    },
    "expectedFailureBehavior": "Channel failure → action=error if send fails without successful fallback"
  }
}
```

---

## Fixture — webhookEvent

**File:** `webhookEvent.workflow.json`

**Triggers**

- `appointmentBooked`
- `webhookEvent`

**Nodes**

- `webhookEvent` — Webhook Event (triggers)
- `end` — End (flow)

**JSON**

```json
{
  "meta": {
    "name": "Fixture — webhookEvent",
    "focusNodeType": "webhookEvent",
    "hospitalIsolation": {
      "hospital_id": 12,
      "rule": "Production workflow lookup MUST filter trigger_type + status(published/active) + hospital_id=booking.hospital_id. No global/null hospital fallback."
    },
    "notes": [
      "Minimal executable fixture for Laravel Automation Engine implementation/tests.",
      "start node omitted (frontend-only; stripped on save).",
      "Valid nodeType + configuration fields from frontend catalog/schemas."
    ]
  },
  "workflow": {
    "name": "Fixture — webhookEvent",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "appointmentBooked",
    "module": "appointments",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_webhookEvent",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "webhookEvent",
            "category": "triggers",
            "label": "Webhook Event",
            "status": "draft",
            "webhookName": "",
            "webhookUrl": "https://api.healthinpocket.in/api/webhooks/{workflow_id}",
            "httpMethod": "POST",
            "secretKey": "",
            "payloadVariables": [
              {
                "key": "",
                "variable": ""
              }
            ],
            "note": "Persisted on node.data in React Flow configuration.nodes[].data"
          }
        },
        {
          "id": "n_end",
          "type": "workflow",
          "position": {
            "x": 560,
            "y": 160
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "outcome": "completed",
            "status": "ready"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_webhookEvent",
          "target": "n_end",
          "type": "smoothstep",
          "sourceHandle": null,
          "targetHandle": null
        }
      ]
    }
  },
  "test": {
    "inputContext": {
      "executionId": "exec_fixture_1",
      "workflowId": 101,
      "workflowName": "Fixture Workflow",
      "triggerPayload": {
        "trigger_type": "webhookEvent"
      },
      "patient": {
        "id": 501,
        "name": "Riya Sharma",
        "patient_name": "Riya Sharma",
        "mobile": "+919876543210",
        "email": "riya@example.com",
        "age": 32,
        "gender": "female"
      },
      "doctor": {
        "id": 77,
        "name": "Dr. Mehta",
        "doctor_name": "Dr. Mehta",
        "department": "Cardiology",
        "mobile": "+919811122233"
      },
      "appointment": {
        "id": 9001,
        "appointment_id": 9001,
        "appointment_date": "2026-08-27",
        "appointment_time": "10:30",
        "status": "Confirmed",
        "branch_name": "Main Campus"
      },
      "hospital": {
        "id": 12,
        "hospital_id": 12,
        "name": "Sunrise Hospital",
        "hospital_name": "Sunrise Hospital",
        "phone": "+912212345678",
        "hospital_phone": "+912212345678"
      },
      "prescription": {},
      "medicine": {},
      "payment": {},
      "invoice": {},
      "organization": {
        "id": 2
      },
      "variables": {},
      "system": {
        "triggered_at": "2026-08-26T06:00:00.000Z",
        "workflow_id": 101
      }
    },
    "expectedNodeExecution": [
      "webhookEvent trigger metadata recorded",
      "end"
    ],
    "expectedOutput": {
      "trigger": {
        "action": "continue",
        "output": {
          "triggered": true,
          "nodeType": "webhookEvent"
        }
      },
      "end": {
        "action": "end",
        "output": {
          "outcome": "completed"
        }
      }
    },
    "expectedNextNode": {
      "n_webhookEvent": "n_end"
    },
    "expectedFailureBehavior": "No outgoing edge → WorkflowExecutor throws"
  }
}
```

---

## Women's Day Wish

**File:** `womensDayWish.workflow.json`

**Triggers**

- `anniversary`

**Nodes**

- `anniversary` — Women's Day (triggers)
- `condition` — Female patients (conditions)
- `sendWhatsApp` — Women's Day wish (messaging)
- `end` — End (flow)
- `end` — End (skipped) (flow)

**JSON**

```json
{
  "meta": {
    "name": "Women's Day Wish",
    "focusNodeType": "anniversary",
    "campaignKey": "womens_day_wish",
    "notes": [
      "Uses anniversaryType=womens_day (extended option). Optional gender condition for safety."
    ]
  },
  "workflow": {
    "name": "Women's Day Wish",
    "organization_id": 2,
    "hospital_id": 12,
    "status": "inactive",
    "trigger": "anniversary",
    "module": "engagement",
    "configuration": {
      "builderVersion": "1",
      "reactFlowVersion": "12.x",
      "campaignKey": "womens_day_wish",
      "viewport": {
        "x": 0,
        "y": 0,
        "zoom": 1
      },
      "nodes": [
        {
          "id": "n_trigger",
          "type": "workflow",
          "position": {
            "x": 40,
            "y": 160
          },
          "data": {
            "nodeType": "anniversary",
            "category": "triggers",
            "label": "Women's Day",
            "anniversaryType": "womens_day",
            "triggerTiming": "on_date",
            "daysBefore": 1,
            "executionTime": "09:00",
            "status": "draft"
          }
        },
        {
          "id": "n_cond",
          "type": "workflow",
          "position": {
            "x": 280,
            "y": 160
          },
          "data": {
            "nodeType": "condition",
            "category": "conditions",
            "label": "Female patients",
            "name": "Female patients",
            "expression": "patient.gender == \"female\"",
            "status": "draft"
          }
        },
        {
          "id": "n_msg",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 80
          },
          "data": {
            "nodeType": "sendWhatsApp",
            "category": "messaging",
            "label": "Women's Day wish",
            "templateId": "",
            "recipient": "patient",
            "message": "Happy Women's Day {{patient_name}}! — {{hospital_name}}",
            "campaignStep": "womens_day_1",
            "status": "draft"
          }
        },
        {
          "id": "n_end_ok",
          "type": "workflow",
          "position": {
            "x": 760,
            "y": 80
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End",
            "status": "draft"
          }
        },
        {
          "id": "n_end_skip",
          "type": "workflow",
          "position": {
            "x": 520,
            "y": 280
          },
          "data": {
            "nodeType": "end",
            "category": "flow",
            "label": "End (skipped)",
            "status": "draft"
          }
        }
      ],
      "edges": [
        {
          "id": "e1",
          "source": "n_trigger",
          "target": "n_cond"
        },
        {
          "id": "e2",
          "source": "n_cond",
          "target": "n_msg",
          "sourceHandle": "true"
        },
        {
          "id": "e3",
          "source": "n_cond",
          "target": "n_end_skip",
          "sourceHandle": "false"
        },
        {
          "id": "e4",
          "source": "n_msg",
          "target": "n_end_ok"
        }
      ]
    }
  }
}
```
