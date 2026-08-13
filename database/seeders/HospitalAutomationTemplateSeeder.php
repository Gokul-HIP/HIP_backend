<?php

namespace Database\Seeders;

use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use Illuminate\Database\Seeder;

class HospitalAutomationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Appointment Confirmation WhatsApp',
                'channel' => 'whatsapp',
                'category' => 'appointment',
                'body' => 'Hi {{PatientName}}, your appointment with Dr. {{DoctorName}} at {{HospitalName}} is confirmed for {{AppointmentDate}} at {{AppointmentTime}}.',
            ],
            [
                'name' => 'Appointment Reminder Push',
                'channel' => 'push',
                'category' => 'appointment',
                'body' => 'Reminder: Appointment with Dr. {{DoctorName}} tomorrow at {{AppointmentTime}}.',
            ],
            [
                'name' => 'Lab Report Ready Email',
                'channel' => 'email',
                'category' => 'lab',
                'body' => 'Dear {{PatientName}}, your lab report for {{LabTestName}} is ready. Please check the app or contact {{HospitalName}}.',
            ],
            [
                'name' => 'Medicine Refill WhatsApp',
                'channel' => 'whatsapp',
                'category' => 'pharmacy',
                'body' => 'Hi {{PatientName}}, your medicine {{MedicineName}} refill is due soon. Reply to reorder from {{HospitalName}}.',
            ],
            [
                'name' => 'Invoice Email',
                'channel' => 'email',
                'category' => 'billing',
                'body' => 'Dear {{PatientName}}, your invoice #{{InvoiceId}} for ₹{{InvoiceAmount}} is generated. Payment status: {{PaymentStatus}}.',
            ],
            [
                'name' => 'Payment Reminder SMS',
                'channel' => 'sms',
                'category' => 'billing',
                'body' => 'Payment pending for invoice #{{InvoiceId}} (₹{{InvoiceAmount}}). Pay now at {{HospitalName}}.',
            ],
            [
                'name' => 'Membership Expiry Email',
                'channel' => 'email',
                'category' => 'membership',
                'body' => 'Hi {{PatientName}}, your {{MembershipTier}} membership expires on {{MembershipExpiry}}. Renew to keep your benefits.',
            ],
            [
                'name' => 'Birthday Wishes WhatsApp',
                'channel' => 'whatsapp',
                'category' => 'engagement',
                'body' => 'Happy Birthday {{PatientName}}! 🎂 {{HospitalName}} wishes you health and happiness. Use code {{CouponCode}} for a special gift.',
            ],
            [
                'name' => 'Feedback Form WhatsApp',
                'channel' => 'whatsapp',
                'category' => 'feedback',
                'body' => 'Hi {{PatientName}}, how was your visit with Dr. {{DoctorName}}? Share feedback: {{FeedbackUrl}}',
            ],
            [
                'name' => 'Lab AI Summary Prompt',
                'channel' => 'ai',
                'category' => 'ai',
                'body' => 'Summarize the lab report {{LabTestName}} for patient {{PatientName}} in simple language suitable for a patient email.',
            ],
            [
                'name' => 'Diabetic Campaign WhatsApp',
                'channel' => 'whatsapp',
                'category' => 'campaign',
                'body' => 'Hi {{PatientName}}, {{HospitalName}} monthly diabetic care tips are here. Book a checkup with Dr. {{DoctorName}} today.',
            ],
        ];

        foreach ($templates as $template) {
            WorkflowMessageTemplate::query()->updateOrCreate(
                ['name' => $template['name'], 'channel' => $template['channel']],
                array_merge($template, ['locale' => 'en', 'is_active' => true, 'version_number' => 1])
            );
        }
    }
}
