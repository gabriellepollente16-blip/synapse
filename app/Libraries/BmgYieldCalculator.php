<?php

namespace App\Libraries;

/**
 * BmgYieldCalculator — computes fertilizer yield analytics.
 *
 * Yield analysis for the BMG (Biodegradable Waste Management) module:
 *   - Yield %         = (output_weight_kg / input_weight_kg) * 100
 *   - Mass reduction  = 100 - yield %
 *
 * These metrics help compare drum performance and waste category efficiency
 * over time.
 */
class BmgYieldCalculator
{
    /**
     * Compute yield percentage.
     *
     * @param float $inputWeightKg  Total weight loaded into the drum
     * @param float $outputWeightKg Final fertilizer weight harvested
     * @return float Yield percentage (0-100), or 0.0 if input is zero
     */
    public static function computeYield(float $inputWeightKg, float $outputWeightKg): float
    {
        if ($inputWeightKg <= 0) {
            return 0.0;
        }

        $yield = ($outputWeightKg / $inputWeightKg) * 100;

        // Cap at 100% to prevent impossible values from floating-point weirdness
        return min(round($yield, 2), 100.0);
    }

    /**
     * Compute mass reduction percentage.
     *
     * Mass reduction = the percentage of input mass that was lost during
     * decomposition (e.g., as CO2, water vapor). A higher mass reduction
     * means more efficient decomposition.
     *
     * @param float $yieldPct Yield percentage (0-100)
     * @return float Mass reduction percentage (0-100)
     */
    public static function computeMassReduction(float $yieldPct): float
    {
        return round(100.0 - $yieldPct, 2);
    }

    /**
     * Convenience method: compute both yield and mass reduction in one call.
     *
     * @return array{yield: float, mass_reduction: float}
     */
    public static function computeAll(float $inputWeightKg, float $outputWeightKg): array
    {
        $yield = self::computeYield($inputWeightKg, $outputWeightKg);
        return [
            'yield'          => $yield,
            'mass_reduction' => self::computeMassReduction($yield),
        ];
    }

    /**
     * Classify yield into performance categories.
     *
     * @param float $yieldPct
     * @return string Category label: 'excellent', 'good', 'fair', 'poor'
     */
    public static function classifyYield(float $yieldPct): string
    {
        return match (true) {
            $yieldPct >= 50 => 'excellent',
            $yieldPct >= 35 => 'good',
            $yieldPct >= 20 => 'fair',
            default         => 'poor',
        };
    }
}
