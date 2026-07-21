<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\BmgYieldCalculator;

/**
 * @internal
 */
final class BmgYieldCalculatorTest extends CIUnitTestCase
{
    public function testComputeYieldBasicCase(): void
    {
        // 30 kg out of 100 kg in = 30%
        $this->assertSame(30.0, BmgYieldCalculator::computeYield(100.0, 30.0));
    }

    public function testComputeYieldWithDecimals(): void
    {
        // 25.5 / 85 * 100 = 30.0
        $this->assertSame(30.0, BmgYieldCalculator::computeYield(85.0, 25.5));
    }

    public function testComputeYieldZeroInputReturnsZero(): void
    {
        $this->assertSame(0.0, BmgYieldCalculator::computeYield(0.0, 5.0));
    }

    public function testComputeYieldNegativeInputReturnsZero(): void
    {
        $this->assertSame(0.0, BmgYieldCalculator::computeYield(-10.0, 5.0));
    }

    public function testComputeYieldCappedAt100(): void
    {
        // Floating-point weirdness, or anomaly: 120 kg out of 100 kg in
        // would yield 120%, but the calculator caps it at 100.
        $this->assertSame(100.0, BmgYieldCalculator::computeYield(100.0, 120.0));
    }

    public function testComputeMassReductionSimple(): void
    {
        // 30% yield means 70% mass reduction
        $this->assertSame(70.0, BmgYieldCalculator::computeMassReduction(30.0));
    }

    public function testComputeMassReductionZeroYield(): void
    {
        $this->assertSame(100.0, BmgYieldCalculator::computeMassReduction(0.0));
    }

    public function testComputeAllReturnsBoth(): void
    {
        $result = BmgYieldCalculator::computeAll(80.0, 24.0);
        $this->assertSame(30.0, $result['yield']);
        $this->assertSame(70.0, $result['mass_reduction']);
    }

    public function testClassifyYieldExcellent(): void
    {
        $this->assertSame('excellent', BmgYieldCalculator::classifyYield(55.0));
        $this->assertSame('excellent', BmgYieldCalculator::classifyYield(100.0));
    }

    public function testClassifyYieldGood(): void
    {
        $this->assertSame('good', BmgYieldCalculator::classifyYield(35.0));
        $this->assertSame('good', BmgYieldCalculator::classifyYield(49.99));
    }

    public function testClassifyYieldFair(): void
    {
        $this->assertSame('fair', BmgYieldCalculator::classifyYield(20.0));
        $this->assertSame('fair', BmgYieldCalculator::classifyYield(34.99));
    }

    public function testClassifyYieldPoor(): void
    {
        $this->assertSame('poor', BmgYieldCalculator::classifyYield(19.99));
        $this->assertSame('poor', BmgYieldCalculator::classifyYield(0.0));
    }
}
