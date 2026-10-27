<?php

namespace Tests\Unit;

use App\Services\TransactionBreakdownBuilder;
use PHPUnit\Framework\TestCase;

class TransactionBreakdownBuilderTest extends TestCase
{
    private TransactionBreakdownBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new TransactionBreakdownBuilder();
    }

    public function test_normal_paid_booking_breakdown_reconciles_to_total_paid(): void
    {
        $result = $this->builder->build(
            ['total_paid' => 105, 'service_charge' => 5],
            ['doctor_booking_id' => 42, 'original_amount' => 100, 'total_amount' => 105, 'service_charge' => 5],
        );

        $this->assertSame('doctor', $result['booking_type']);
        $this->assertEquals(105, $result['total_paid']);
        $this->assertEquals(0, $result['membership_discount'] + $result['reward_discount'] + $result['coin_discount']);
        $this->assertReconciles($result);
    }

    public function test_reward_tier_discount_is_attributed_only_with_usage_ledger(): void
    {
        $result = $this->builder->build(
            ['total_paid' => 95, 'service_charge' => 5],
            ['doctor_booking_id' => 7, 'original_amount' => 100, 'discount_amount' => 10, 'total_amount' => 95, 'service_charge' => 5],
            ['consultation_fee' => 100, 'total_discount' => 10],
            null,
            ['tier_name' => 'Silver', 'tier_percentage' => 5],
        );

        $this->assertEquals(10, $result['reward_discount']);
        $this->assertSame('Silver', $result['reward_tier_name']);
        $this->assertEquals(5, $result['reward_tier_percentage']);
        $this->assertEquals(95, $result['total_paid']);
        $this->assertReconciles($result);
    }

    public function test_membership_free_consultation_shows_full_fee_discount_and_service_charge(): void
    {
        $result = $this->builder->build(
            ['total_paid' => 5, 'service_charge' => 5],
            ['doctor_booking_id' => 8, 'original_amount' => 100, 'discount_amount' => 100, 'total_amount' => 5, 'service_charge' => 5],
            ['consultation_fee' => 100, 'total_discount' => 100],
            ['usage_type' => 'free_consultation'],
        );

        $this->assertEquals(100, $result['original_amount']);
        $this->assertEquals(100, $result['membership_discount']);
        $this->assertEquals(5, $result['service_charge']);
        $this->assertEquals(5, $result['total_paid']);
        $this->assertReconciles($result);
    }

    public function test_coin_discount_uses_booking_coin_count_and_configured_rate_when_transaction_has_no_value(): void
    {
        $result = $this->builder->build(
            ['total_paid' => 90],
            ['doctor_booking_id' => 9, 'original_amount' => 100, 'discount_amount' => 10, 'coins_used' => 5, 'total_amount' => 90],
            ['consultation_fee' => 100, 'total_discount' => 10, 'coins_used' => 5],
            null,
            null,
            null,
            2,
        );

        $this->assertSame(5, $result['coins_used']);
        $this->assertEquals(10, $result['coin_discount']);
        $this->assertSame('configured_rate', $result['coin_discount_source']);
        $this->assertEquals(90, $result['total_paid']);
        $this->assertReconciles($result);
    }

    public function test_reward_and_coin_discounts_are_split_using_coin_value_then_reward_ledger(): void
    {
        $result = $this->builder->build(
            ['total_paid' => 85],
            ['doctor_booking_id' => 10, 'original_amount' => 100, 'discount_amount' => 15, 'coins_used' => 5, 'total_amount' => 85],
            ['consultation_fee' => 100, 'total_discount' => 15, 'coins_used' => 5],
            null,
            ['tier_name' => 'Silver', 'tier_percentage' => 5],
            null,
            1,
        );

        $this->assertEquals(5, $result['coin_discount']);
        $this->assertEquals(10, $result['reward_discount']);
        $this->assertEquals(85, $result['total_paid']);
        $this->assertReconciles($result);
    }

    public function test_legacy_unexplained_discount_is_not_assigned_to_reward_without_ledger(): void
    {
        $result = $this->builder->build(
            ['total_paid' => 90],
            ['doctor_booking_id' => 11, 'original_amount' => 100, 'discount_amount' => 10, 'total_amount' => 90],
            ['consultation_fee' => 100, 'total_discount' => 10],
        );

        $this->assertEquals(0, $result['reward_discount']);
        $this->assertEquals(10, $result['unexplained_discount']);
        $this->assertNull($result['reward_tier_name']);
        $this->assertReconciles($result);
    }

    public function test_pharmacy_invoice_keeps_its_recorded_coin_charge_without_reward_or_membership(): void
    {
        $result = $this->builder->build(
            ['transaction_amount' => 100, 'discount_amount' => 5, 'service_charge' => 2, 'gst' => 1, 'total_paid' => 98],
            ['original_amount' => 100, 'total_amount' => 98, 'pharmacy_order_id' => 3, 'service_charge' => 2, 'gst' => 1],
            null,
            null,
            null,
            ['id' => 3, 'subtotal' => 100, 'coins_applied' => 1],
        );

        $this->assertSame('pharmacy', $result['booking_type']);
        $this->assertEquals(5, $result['coin_discount']);
        $this->assertEquals(0, $result['reward_discount']);
        $this->assertEquals(98, $result['total_paid']);
        $this->assertReconciles($result);
    }

    private function assertReconciles(array $result): void
    {
        $calculated = $result['original_amount']
            - $result['membership_discount']
            - $result['reward_discount']
            - $result['coin_discount']
            - $result['unexplained_discount']
            + $result['service_charge']
            + $result['gst']
            + $result['gateway_charge'];

        $this->assertEquals($result['total_paid'], round($calculated, 2));
    }
}
