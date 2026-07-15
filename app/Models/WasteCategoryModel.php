<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * WasteCategoryModel — manages the taxonomy of biodegradable waste types.
 *
 * Used by the BMG module to tag batches by waste type, enabling
 * comparative analysis of decomposition duration and yield across
 * different waste compositions.
 */
class WasteCategoryModel extends Model
{
    protected $table            = 'waste_categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'code', 'name', 'description', 'expected_yield_pct', 'reference_duration_days', 'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'code'  => 'required|max_length[50]|is_unique[waste_categories.code,id,{id}]',
        'name'  => 'required|max_length[100]',
        'is_active' => 'in_list[0,1]',
    ];

    /**
     * Get all active waste categories (for dropdowns).
     */
    public function getActive(): array
    {
        return $this->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Find a category by its unique code.
     */
    public function findByCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }

    /**
     * Resolve the reference decomposition duration for a category, with
     * sensible fallbacks. Returns an int days count.
     */
    public function getReferenceDuration(int $categoryId): int
    {
        $cat = $this->find($categoryId);
        if (! $cat) {
            return 45; // hard fallback
        }
        $days = (int) ($cat['reference_duration_days'] ?? 0);
        if ($days <= 0) {
            // Fall back to a yield-based heuristic: higher yield pct →
            // faster decomposition. Tuned for typical campus BMG.
            $yield = (float) ($cat['expected_yield_pct'] ?? 30);
            $days  = (int) round(60 - ($yield * 0.7));
            $days  = max(20, min(90, $days));
        }
        return $days;
    }
}
