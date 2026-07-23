<?php

/** @return array<int, array{name: string, module: string, trigger_type: string, definition: array}> */

$node = static fn (string $id, string $nodeType, array $data = [], float $x = 0, float $y = 0) => [
    'id' => $id,
    'type' => 'workflow',
    'position' => ['x' => $x, 'y' => $y],
    'data' => array_merge(['nodeType' => $nodeType], $data),
];

$edge = static fn (string $id, string $source, string $target, ?string $sourceHandle = null) => array_filter([
    'id' => $id,
    'source' => $source,
    'target' => $target,
    'sourceHandle' => $sourceHandle,
]);

return [
    [
        'name' => 'Appointment Booked Confirmation',
        'module' => 'appointment',
        'trigger_type' => 'appointmentBooked',
        'definition' => [
            'builderVersion' => '1',
            'nodes' => [
                $node('t1', 'appointmentBooked', [], 100, 100),
                $node('d1', 'delay', ['type' => 'minutes', 'value' => 5], 300, 100),
                $node('w1', 'sendWhatsApp', ['messageTemplate' => 'Hi {{PatientName}}, your appointment with Dr. {{DoctorName}} at {{HospitalName}} is confirmed for {{AppointmentDate}} at {{AppointmentTime}}.'], 500, 100),
                $node('e1', 'end', [], 700, 100),
            ],
            'edges' => [
                $edge('e-t1-d1', 't1', 'd1'),
                $edge('e-d1-w1', 'd1', 'w1'),
                $edge('e-w1-e1', 'w1', 'e1'),
            ],
        ],
    ],
    [
        'name' => 'Appointment Tomorrow Reminder',
        'module' => 'appointment',
        'trigger_type' => 'appointmentBooked',
        'definition' => [
            'builderVersion' => '1',
            'nodes' => [
                $node('t1', 'appointmentBooked', [], 100, 100),
                $node('d1', 'delay', ['type' => 'hours', 'value' => 24], 300, 100),
                $node('p1', 'sendPush', ['messageTemplate' => 'Reminder: Appointment with Dr. {{DoctorName}} on {{AppointmentDate}} at {{AppointmentTime}}.'], 500, 100),
                $node('e1', 'end', [], 700, 100),
            ],
            'edges' => [
                $edge('e-t1-d1', 't1', 'd1'),
                $edge('e-d1-p1', 'd1', 'p1'),
                $edge('e-p1-e1', 'p1', 'e1'),
            ],
        ],
    ],
    [
        'name' => 'Lab Report Ready Notification',
        'module' => 'lab',
        'trigger_type' => 'labReportReady',
        'definition' => [
            'builderVersion' => '1',
            'nodes' => [
                $node('t1', 'labReportReady', [], 100, 100),
                $node('c1', 'condition', ['rules' => ['field' => 'patient.segment', 'compare' => 'exists', 'value' => true]], 300, 100),
                $node('p1', 'sendPush', ['messageTemplate' => 'Your lab report {{LabTestName}} is ready.'], 500, 50),
                $node('m1', 'sendEmail', ['messageTemplate' => 'Dear {{PatientName}}, your lab report for {{LabTestName}} is ready at {{HospitalName}}.'], 500, 150),
                $node('e1', 'end', [], 700, 100),
            ],
            'edges' => [
                $edge('e-t1-c1', 't1', 'c1'),
                $edge('e-c1-p1', 'c1', 'p1', 'true'),
                $edge('e-c1-m1', 'c1', 'm1', 'false'),
                $edge('e-p1-e1', 'p1', 'e1'),
                $edge('e-m1-e1', 'm1', 'e1'),
            ],
        ],
    ],
    [
        'name' => 'Medicine Refill Due Reminder',
        'module' => 'pharmacy',
        'trigger_type' => 'medicineRefillDue',
        'definition' => [
            'builderVersion' => '1',
            'nodes' => [
                $node('t1', 'medicineRefillDue', [], 100, 100),
                $node('c1', 'condition', ['rules' => ['field' => 'patient.segment', 'compare' => 'exists', 'value' => true]], 300, 100),
                $node('w1', 'sendWhatsApp', ['messageTemplate' => 'Hi {{PatientName}}, your medicine {{MedicineName}} refill is due. Contact {{HospitalName}}.'], 500, 100),
                $node('e1', 'end', [], 700, 100),
            ],
            'edges' => [
                $edge('e-t1-c1', 't1', 'c1'),
                $edge('e-c1-w1', 'c1', 'w1', 'true'),
                $edge('e-w1-e1', 'w1', 'e1'),
            ],
        ],
    ],
    [
        'name' => 'Invoice Generated + Payment Reminder',
        'module' => 'billing',
        'trigger_type' => 'invoiceGenerated',
        'definition' => [
            'builderVersion' => '1',
            'nodes' => [
                $node('t1', 'invoiceGenerated', [], 100, 100),
                $node('m1', 'sendEmail', ['messageTemplate' => 'Invoice #{{InvoiceId}} for ₹{{InvoiceAmount}} has been generated.'], 300, 100),
                $node('d1', 'delay', ['type' => 'days', 'value' => 3], 500, 100),
                $node('s1', 'sendSMS', ['messageTemplate' => 'Payment pending for invoice #{{InvoiceId}} (₹{{InvoiceAmount}}).'], 700, 100),
                $node('e1', 'end', [], 900, 100),
            ],
            'edges' => [
                $edge('e-t1-m1', 't1', 'm1'),
                $edge('e-m1-d1', 'm1', 'd1'),
                $edge('e-d1-s1', 'd1', 's1'),
                $edge('e-s1-e1', 's1', 'e1'),
            ],
        ],
    ],
    [
        'name' => 'Birthday Engagement',
        'module' => 'engagement',
        'trigger_type' => 'birthday',
        'definition' => [
            'builderVersion' => '1',
            'nodes' => [
                $node('t1', 'birthday', [], 100, 100),
                $node('w1', 'sendWhatsApp', ['messageTemplate' => 'Happy Birthday {{PatientName}}! Use coupon {{CouponCode}} at {{HospitalName}}.'], 300, 100),
                $node('e1', 'end', [], 500, 100),
            ],
            'edges' => [
                $edge('e-t1-w1', 't1', 'w1'),
                $edge('e-w1-e1', 'w1', 'e1'),
            ],
        ],
    ],
    [
        'name' => 'Appointment Feedback Flow',
        'module' => 'feedback',
        'trigger_type' => 'appointmentCompleted',
        'definition' => [
            'builderVersion' => '1',
            'nodes' => [
                $node('t1', 'appointmentCompleted', [], 100, 100),
                $node('d1', 'delay', ['type' => 'hours', 'value' => 2], 300, 100),
                $node('w1', 'sendWhatsApp', ['messageTemplate' => 'Hi {{PatientName}}, please share feedback for Dr. {{DoctorName}}: {{FeedbackUrl}}'], 500, 100),
                $node('c1', 'condition', ['rules' => ['field' => 'rating', 'compare' => 'less_than', 'value' => 3]], 700, 100),
                $node('cr1', 'createRecord', ['table' => 'support_tickets', 'values' => ['subject' => 'Low rating feedback', 'patient_id' => '{{PatientId}}']], 900, 100),
                $node('e1', 'end', [], 1100, 100),
            ],
            'edges' => [
                $edge('e-t1-d1', 't1', 'd1'),
                $edge('e-d1-w1', 'd1', 'w1'),
                $edge('e-w1-c1', 'w1', 'c1'),
                $edge('e-c1-cr1', 'c1', 'cr1', 'true'),
                $edge('e-cr1-e1', 'cr1', 'e1'),
                $edge('e-c1-e1', 'c1', 'e1', 'false'),
            ],
        ],
    ],
    [
        'name' => 'Lab Report AI Summary',
        'module' => 'ai',
        'trigger_type' => 'labReportReady',
        'definition' => [
            'builderVersion' => '1',
            'nodes' => [
                $node('t1', 'labReportReady', [], 100, 100),
                $node('a1', 'aiPrompt', ['promptType' => 'lab_report_summary', 'outputChannel' => 'email'], 300, 100),
                $node('e1', 'end', [], 500, 100),
            ],
            'edges' => [
                $edge('e-t1-a1', 't1', 'a1'),
                $edge('e-a1-e1', 'a1', 'e1'),
            ],
        ],
    ],
    [
        'name' => 'Diabetic Patient Campaign',
        'module' => 'campaign',
        'trigger_type' => 'campaignTriggered',
        'definition' => [
            'builderVersion' => '1',
            'nodes' => [
                $node('t1', 'campaignTriggered', ['segment' => 'diabetic_patients'], 100, 100),
                $node('w1', 'sendWhatsApp', ['messageTemplate' => 'Hi {{PatientName}}, monthly diabetic care tips from {{HospitalName}}.'], 300, 100),
                $node('m1', 'sendEmail', ['messageTemplate' => 'Dear {{PatientName}}, your monthly diabetic care update from {{HospitalName}}.'], 300, 200),
                $node('e1', 'end', [], 500, 150),
            ],
            'edges' => [
                $edge('e-t1-w1', 't1', 'w1'),
                $edge('e-t1-m1', 't1', 'm1'),
                $edge('e-w1-e1', 'w1', 'e1'),
                $edge('e-m1-e1', 'm1', 'e1'),
            ],
        ],
    ],
];
