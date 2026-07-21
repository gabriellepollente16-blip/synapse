<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\BmgDurationCalculator;

/**
 * @internal
 */
final class BmgDurationCalculatorTest extends CIUnitTestCase
{
    public function testComputeDurationDaysSimple(): void
    {
        // Jan 1 -> Jan 11 is 11 days inclusive of both endpoints
        $this->assertSame(11, BmgDurationCalculator::computeDurationDays('2024-01-01', '2024-01-11'));
    }

    public function testComputeDurationDaysSameDay(): void
    {
        // Same day = 1 day (inclusive)
        $this->assertSame(1, BmgDurationCalculator::computeDurationDays('2024-06-01', '2024-06-01'));
    }

    public function testComputeDurationDaysEmptyInputsReturnZero(): void
    {
        $this->assertSame(0, BmgDurationCalculator::computeDurationDays('', '2024-01-01'));
        $this->assertSame(0, BmgDurationCalculator::computeDurationDays('2024-01-01', ''));
        $this->assertSame(0, BmgDurationCalculator::computeDurationDays('', ''));
    }

    public function testComputeDurationDaysInvalidDatesReturnZero(): void
    {
        $this->assertSame(0, BmgDurationCalculator::computeDurationDays('not-a-date', '2024-01-01'));
    }

    public function testComputeDurationDaysReversedOrder(): void
    {
        // diff->days is always positive regardless of order
        $this->assertSame(11, BmgDurationCalculator::computeDurationDays('2024-01-11', '2024-01-01'));
    }

    public function testComputeDurationToToday(): void
    {
        $today = date('Y-m-d');
        $sevenAgo = date('Y-m-d', strtotime('-7 days'));
        // -7 days to today = 8 days inclusive
        $this->assertSame(8, BmgDurationCalculator::computeDurationToToday($sevenAgo));
    }

    public function testAverageDurationHandlesEmpty(): void
    {
        $this->assertSame(0.0, BmgDurationCalculator::averageDuration([]));
    }

    public function testAverageDurationSkipsNulls(): void
    {
        $batches = [
            ['duration_days' => 30],
            ['duration_days' => null],
            ['duration_days' => 60],
            ['duration_days' => 45],
        ];
        // (30 + 60 + 45) / 3 = 45.0
        $this->assertSame(45.0, BmgDurationCalculator::averageDuration($batches));
    }

    public function testAverageDurationAllNulls(): void
    {
        $batches = [
            ['duration_days' => null],
            ['duration_days' => null],
        ];
        $this->assertSame(0.0, BmgDurationCalculator::averageDuration($batches));
    }

    public function testClassifyDurationBoundaries(): void
    {
        $this->assertSame('fast', BmgDurationCalculator::classifyDuration(0));
        $this->assertSame('fast', BmgDurationCalculator::classifyDuration(14));
        $this->assertSame('normal', BmgDurationCalculator::classifyDuration(15));
        $this->assertSame('normal', BmgDurationCalculator::classifyDuration(30));
        $this->assertSame('slow', BmgDurationCalculator::classifyDuration(31));
        $this->assertSame('slow', BmgDurationCalculator::classifyDuration(60));
        $this->assertSame('stalled', BmgDurationCalculator::classifyDuration(61));
        $this->assertSame('stalled', BmgDurationCalculator::classifyDuration(365));
    }

    public function testExpectedCompletionDateAddsDays(): void
    {
        // Jan 1 + 45 days = Feb 15
        $this->assertSame('2024-02-15', BmgDurationCalculator::expectedCompletionDate('2024-01-01', 45));
    }

    public function testExpectedCompletionDateZeroDefaultsTo45(): void
    {
        // Jan 1 + 45 days (default) = Feb 15
        $this->assertSame('2024-02-15', BmgDurationCalculator::expectedCompletionDate('2024-01-01', 0));
    }

    public function testExpectedCompletionDateNegativeDefaultsTo45(): void
    {
        $this->assertSame('2024-02-15', BmgDurationCalculator::expectedCompletionDate('2024-01-01', -5));
    }

    public function testDaysUntilExpectedReturnsPositiveForFuture(): void
    {
        $future = date('Y-m-d', strtotime('+10 days'));
        $this->assertSame(10, BmgDurationCalculator::daysUntilExpected($future));
    }

    public function testDaysUntilExpectedReturnsNegativeForPast(): void
    {
        $past = date('Y-m-d', strtotime('-5 days'));
        $this->assertSame(-5, BmgDurationCalculator::daysUntilExpected($past));
    }

    public function testDaysUntilExpectedNullForEmpty(): void
    {
        $this->assertNull(BmgDurationCalculator::daysUntilExpected(null));
        $this->assertNull(BmgDurationCalculator::daysUntilExpected(''));
    }

    public function testProgressPercentAtStart(): void
    {
        $start = date('Y-m-d', strtotime('-1 day'));
        $expected = date('Y-m-d', strtotime('+44 days'));
        // 1 day out of 45 = 2%
        $this->assertSame(2, BmgDurationCalculator::progressPercent($start, $expected));
    }

    public function testProgressPercentAtEnd(): void
    {
        // start + duration = today
        $start = date('Y-m-d', strtotime('-44 days'));
        $expected = date('Y-m-d', strtotime('+1 day'));
        // 44 / 45 = 98%
        $this->assertSame(98, BmgDurationCalculator::progressPercent($start, $expected));
    }

    public function testProgressPercentOverdue(): void
    {
        // expected date is in the past, we are past 100%
        $start = date('Y-m-d', strtotime('-60 days'));
        $expected = date('Y-m-d', strtotime('-15 days'));
        $this->assertSame(100, BmgDurationCalculator::progressPercent($start, $expected));
    }

    public function testProgressPercentEmptyInputs(): void
    {
        $this->assertSame(0, BmgDurationCalculator::progressPercent(null, null));
        $this->assertSame(0, BmgDurationCalculator::progressPercent('', ''));
    }

    public function testProgressPercentSameDayReturnsHundred(): void
    {
        // start == expected: 0/0 guard returns 100
        $this->assertSame(100, BmgDurationCalculator::progressPercent('2024-06-01', '2024-06-01'));
    }
}
