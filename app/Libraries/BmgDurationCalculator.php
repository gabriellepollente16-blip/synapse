<?php

namespace App\Libraries;

/**
 * BmgDurationCalculator — computes decomposition duration.
 *
 * Computes the number of days a batch took to fully decompose, from
 * start_date to completion_date. Used for comparing drum designs and
 * waste category performance.
 */
class BmgDurationCalculator
{
    /**
     * Compute duration in days between two dates.
     *
     * @param string $startDate     Y-m-d format
     * @param string $endDate       Y-m-d format
     * @return int Number of days (inclusive of both start and end day)
     */
    public static function computeDurationDays(string $startDate, string $endDate): int
    {
        if (empty($startDate) || empty($endDate)) {
            return 0;
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $diff = $start->diff($end);
            return (int) $diff->days + 1;  // +1 to count both start and end day
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Compute duration in days as of today (for in-progress batches).
     */
    public static function computeDurationToToday(string $startDate): int
    {
        return self::computeDurationDays($startDate, date('Y-m-d'));
    }

    /**
     * Compute average duration for a set of batches.
     *
     * @param array $batches Array of batch records (each must have 'duration_days')
     * @return float Average duration in days, or 0 if no batches
     */
    public static function averageDuration(array $batches): float
    {
        if (empty($batches)) {
            return 0.0;
        }

        $sum = 0;
        $count = 0;
        foreach ($batches as $b) {
            if (isset($b['duration_days']) && $b['duration_days'] !== null) {
                $sum += (int) $b['duration_days'];
                $count++;
            }
        }

        return $count > 0 ? round($sum / $count, 1) : 0.0;
    }

    /**
     * Classify a duration into speed categories.
     *
     * @param int $days
     * @return string 'fast', 'normal', 'slow', or 'stalled'
     */
    public static function classifyDuration(int $days): string
    {
        return match (true) {
            $days <= 14  => 'fast',
            $days <= 30  => 'normal',
            $days <= 60  => 'slow',
            default      => 'stalled',
        };
    }

    /**
     * Compute the expected completion date given a start date and the
     * category's reference duration. Returns Y-m-d format.
     */
    public static function expectedCompletionDate(string $startDate, int $days): string
    {
        if ($days <= 0) {
            $days = 45;
        }
        try {
            $start = new \DateTime($startDate);
            $start->modify('+' . $days . ' days');
            return $start->format('Y-m-d');
        } catch (\Exception $e) {
            return $startDate;
        }
    }

    /**
     * Compute days remaining until the expected completion date.
     * Positive = days until ready, negative = overdue.
     */
    public static function daysUntilExpected(?string $expectedDate, ?string $today = null): ?int
    {
        if (empty($expectedDate)) {
            return null;
        }
        try {
            $today  = $today ?? date('Y-m-d');
            $exp    = new \DateTime($expectedDate);
            $now    = new \DateTime($today);
            $diff   = $now->diff($exp);
            $sign   = $exp < $now ? -1 : 1;
            return (int) $diff->days * $sign;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Compute the progress percentage (0-100) of a batch's decomposition,
     * based on how many days have elapsed vs. the expected duration.
     */
    public static function progressPercent(?string $startDate, ?string $expectedDate): int
    {
        if (empty($startDate) || empty($expectedDate)) {
            return 0;
        }
        try {
            $start = new \DateTime($startDate);
            $exp   = new \DateTime($expectedDate);
            $now   = new \DateTime(date('Y-m-d'));
            $totalSpan = (int) $start->diff($exp)->days;
            if ($totalSpan <= 0) {
                return 100;
            }
            $elapsed = (int) $start->diff($now)->days;
            $pct = (int) round(($elapsed / $totalSpan) * 100);
            return max(0, min(100, $pct));
        } catch (\Exception $e) {
            return 0;
        }
    }
}
