<?php

namespace Modules\SubscriptionManager\Tests\Unit;

use Modules\SubscriptionManager\Services\SubscriptionService;
use PHPUnit\Framework\TestCase;

class SubscriptionDiscountCalculationTest extends TestCase
{
    public function test_calculates_discount_percentage_from_original_and_discounted_prices(): void
    {
        $discount = SubscriptionService::calculateDiscountData(300000, 50, 'حسم موظفة الاستقبال');

        $this->assertTrue($discount['has_discount']);
        $this->assertSame(50.0, $discount['discount_percentage']);
        $this->assertSame(150000.0, $discount['discount_amount']);
        $this->assertSame(150000.0, $discount['discounted_total']);
        $this->assertSame('حسم موظفة الاستقبال', $discount['discount_reason']);
    }

    public function test_calculates_discount_percentage_from_entered_price(): void
    {
        // Receptionist enters 150k for a 300k subscription -> 50%
        $pct50 = SubscriptionService::calculateDiscountPercentageFromPrice(300000, 150000);
        $this->assertSame(50.0, $pct50);

        // Receptionist enters 200k for a 300k subscription -> 33.33%
        $pct33 = SubscriptionService::calculateDiscountPercentageFromPrice(300000, 200000);
        $this->assertSame(33.33, $pct33);

        // Receptionist enters 0 for a 300k subscription -> 100% discount
        $pct100 = SubscriptionService::calculateDiscountPercentageFromPrice(300000, 0);
        $this->assertSame(100.0, $pct100);

        // Same price (no discount) -> 0%
        $pct0 = SubscriptionService::calculateDiscountPercentageFromPrice(300000, 300000);
        $this->assertSame(0.0, $pct0);
    }

    public function test_resolves_discount_options_from_percentage(): void
    {
        $options = [
            'is_discount' => true,
            'discount_percentage' => 50,
            'discount_reason' => 'حسم خاص',
        ];

        $data = SubscriptionService::resolveDiscountOptions(300000, $options);

        $this->assertTrue($data['has_discount']);
        $this->assertSame(50.0, $data['discount_percentage']);
        $this->assertSame(150000.0, $data['discount_amount']);
        $this->assertSame(150000.0, $data['discounted_total']);
        $this->assertSame('حسم خاص', $data['discount_reason']);
    }

    public function test_resolves_discount_options_from_discount_amount(): void
    {
        // Receptionist provided discount amount 150k directly
        $options = [
            'is_discount' => true,
            'discount_amount' => 150000,
        ];

        $data = SubscriptionService::resolveDiscountOptions(300000, $options);

        $this->assertTrue($data['has_discount']);
        $this->assertSame(50.0, $data['discount_percentage']);
        $this->assertSame(150000.0, $data['discount_amount']);
        $this->assertSame(150000.0, $data['discounted_total']);
    }

    public function test_resolves_discount_options_from_discounted_price(): void
    {
        // Receptionist provided discounted final price 150k
        $options = [
            'is_discount' => true,
            'discounted_price' => 150000,
        ];

        $data = SubscriptionService::resolveDiscountOptions(300000, $options);

        $this->assertTrue($data['has_discount']);
        $this->assertSame(50.0, $data['discount_percentage']);
        $this->assertSame(150000.0, $data['discount_amount']);
        $this->assertSame(150000.0, $data['discounted_total']);
    }

    public function test_resolves_no_discount_when_is_discount_is_false(): void
    {
        $options = [
            'is_discount' => false,
            'discount_percentage' => 50, // Should be ignored because is_discount is explicitly false
        ];

        $data = SubscriptionService::resolveDiscountOptions(300000, $options);

        $this->assertFalse($data['has_discount']);
        $this->assertSame(0.0, $data['discount_percentage']);
        $this->assertSame(0.0, $data['discount_amount']);
        $this->assertSame(300000.0, $data['discounted_total']);
    }

    public function test_calculates_split_discounts_for_coach_and_branch_separately(): void
    {
        $splitDiscount = SubscriptionService::calculateSplitDiscountData(200000, 100000, 20, 10);

        $this->assertSame(160000.0, $splitDiscount['coach_discounted_total']);
        $this->assertSame(90000.0, $splitDiscount['branch_discounted_total']);
        $this->assertSame(250000.0, $splitDiscount['total_after_discount']);
        $this->assertSame(40000.0, $splitDiscount['coach_discount_amount']);
        $this->assertSame(10000.0, $splitDiscount['branch_discount_amount']);
    }
}
