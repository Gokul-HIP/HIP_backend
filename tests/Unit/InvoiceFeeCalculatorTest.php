<?php

namespace Tests\Unit;

use App\Services\InvoiceFeeCalculator;
use Tests\TestCase;

class InvoiceFeeCalculatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'settings.fees.service_charges' => 3,
            'settings.fees.payment_gateway_charges' => 2,
            'settings.fees.gst_percent' => 5,
            'services.service_charges_percent' => 3,
            'services.payment_gateway_charges_percent' => 2,
            'services.gst_percent' => 5,
        ]);
    }

    public function test_booking_breakdown_uses_service_charge_percent_on_discounted_amount(): void
    {
        $breakdown = app(InvoiceFeeCalculator::class)->bookingBreakdown(1000, 100);

        $this->assertSame(1000.0, $breakdown['original_amount']);
        $this->assertSame(100.0, $breakdown['discount_amount']);
        $this->assertSame(900.0, $breakdown['discounted_amount']);
        $this->assertSame(27.0, $breakdown['service_charges']);
        $this->assertSame(0.0, $breakdown['payment_gateway_charges']);
        $this->assertSame(0.0, $breakdown['gst_amount']);
        $this->assertSame(927.0, $breakdown['invoice_total']);
    }

    public function test_hospital_bill_applies_all_configured_percents_on_subtotal(): void
    {
        $breakdown = app(InvoiceFeeCalculator::class)->hospitalBillBreakdown(900, 100);

        $this->assertSame(1000.0, $breakdown['original_amount']);
        $this->assertSame(100.0, $breakdown['discount_amount']);
        $this->assertSame(900.0, $breakdown['discounted_amount']);
        $this->assertSame(27.0, $breakdown['service_charges']);
        $this->assertSame(18.0, $breakdown['payment_gateway_charges']);
        $this->assertSame(45.0, $breakdown['gst_amount']);
        $this->assertSame(990.0, $breakdown['invoice_total']);
    }
}
