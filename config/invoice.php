<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server-rendered invoice layout (placeholders only)
    |--------------------------------------------------------------------------
    |
    | Admins may override this via the settings key invoice_layout.
    | Placeholders are replaced from an allowlist; unknown {{tags}} are removed.
    |
    */

    'layout' => <<<'HTML'
<div class="invoice-doc">
  <p><strong>Hospital ID:</strong> {{hospital_id}}</p>
  <h1>{{invoice_title}}</h1>
  <p><strong>Hospital Name:</strong> {{hospital_name}}</p>
  <p>{{hospital_address}}</p>
  <p>{{hospital_contact}}</p>
  <p><strong>Invoice number:</strong> {{invoice_id}}</p>
  <p><strong>Invoice date:</strong> {{invoice_date}}</p>

  <h2>Patient</h2>
  <p><strong>Name:</strong> {{patient_name}}</p>
  <p><strong>Patient ID:</strong> {{patient_id}}</p>
  <p><strong>Contact:</strong> {{patient_contact}}</p>

  <h2>Service</h2>
  <p><strong>Type:</strong> {{service_type}}</p>
  <p><strong>Reference:</strong> {{service_reference}}</p>
  <p>{{service_description}}</p>

  <h2>Amount</h2>
  <p>Original Amount: ₹{{original_amount}}</p>
  <p>Discount: ₹{{discount_amount}}</p>
  <p>Discounted Amount: ₹{{discounted_amount}}</p>
  <p>Service Charges: ₹{{service_charges}}</p>
  <p>Payment Gateway Charges: ₹{{payment_gateway_charges}}</p>
  <p>GST: ₹{{gst_amount}}</p>
  <p><strong>Final Total: ₹{{invoice_total}}</strong></p>

  <h2>Payment</h2>
  <p>Status: {{payment_status}}</p>
  <p>Method: {{payment_method}}</p>
  <p>Reference: {{transaction_id}}</p>
</div>
HTML

];
