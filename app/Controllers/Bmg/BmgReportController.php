<?php

namespace App\Controllers\Bmg;

use App\Controllers\BaseController;
use App\Models\BmgBatchModel;
use App\Models\BmgDrumModel;
use App\Models\BmgOutputModel;
use App\Libraries\BmgYieldCalculator;
use App\Libraries\BmgDurationCalculator;

/**
 * BmgReportController — analytics and exports for the BMG module.
 *
 * Reports:
 *   - Yield by Drum          (bar chart + table)
 *   - Duration by Waste Type (line chart)
 *   - Monthly Totals         (input, output, fertilizer)
 *   - Drum Utilization       (active vs idle)
 */
class BmgReportController extends BaseController
{
    protected BmgBatchModel $batchModel;
    protected BmgDrumModel $drumModel;
    protected BmgOutputModel $outputModel;

    public function __construct()
    {
        $this->batchModel  = new BmgBatchModel();
        $this->drumModel   = new BmgDrumModel();
        $this->outputModel = new BmgOutputModel();
    }

    /**
     * Show the main reports dashboard.
     */
    public function index()
    {
        return view('bmg/reports/index', [
            'title'            => 'BMG Reports — SYNAPSE',
            'heading'          => 'BMG Analytics & Reports',
            'yieldByDrum'      => $this->getYieldByDrum(),
            'durationByWaste'  => $this->getDurationByWasteType(),
            'monthlyTotals'    => $this->getMonthlyTotals(),
            'drumUtilization'  => $this->getDrumUtilization(),
        ]);
    }

    /**
     * Average yield percentage per drum.
     */
    public function getYieldByDrum(int $days = 90): array
    {
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));

        return $this->batchModel
            ->select('bmg_drums.drum_code, bmg_drums.name AS drum_name, AVG(bmg_batches.yield_percentage) AS avg_yield, COUNT(bmg_batches.id) AS batch_count')
            ->join('bmg_drums', 'bmg_drums.id = bmg_batches.drum_id')
            ->where('bmg_batches.status', 'completed')
            ->where('bmg_batches.yield_percentage IS NOT NULL')
            ->where('bmg_batches.completion_date >=', $cutoff)
            ->groupBy('bmg_drums.id')
            ->orderBy('avg_yield', 'DESC')
            ->findAll();
    }

    /**
     * Average duration (days) per waste category.
     */
    public function getDurationByWasteType(int $days = 90): array
    {
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));

        return $this->batchModel
            ->select('waste_categories.name AS waste_name, AVG(bmg_batches.duration_days) AS avg_duration, COUNT(bmg_batches.id) AS batch_count')
            ->join('waste_categories', 'waste_categories.id = bmg_batches.waste_category_id')
            ->where('bmg_batches.status', 'completed')
            ->where('bmg_batches.duration_days IS NOT NULL')
            ->where('bmg_batches.completion_date >=', $cutoff)
            ->groupBy('waste_categories.id')
            ->orderBy('avg_duration', 'ASC')
            ->findAll();
    }

    /**
     * Monthly input/output totals for the past N months.
     */
    public function getMonthlyTotals(int $months = 6): array
    {
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $startOfMonth = date('Y-m-01', strtotime("-{$i} months"));
            $endOfMonth   = date('Y-m-t', strtotime("-{$i} months"));
            $monthLabel   = date('M Y', strtotime("-{$i} months"));

            // Total input for the month
            $inputTotal = $this->batchModel
                ->selectSum('input_weight_kg')
                ->where('start_date >=', $startOfMonth)
                ->where('start_date <=', $endOfMonth)
                ->get()
                ->getRowArray()['input_weight_kg'] ?? 0;

            // Total output for the month
            $outputTotal = $this->outputModel
                ->selectSum('output_weight_kg')
                ->where('harvest_date >=', $startOfMonth)
                ->where('harvest_date <=', $endOfMonth)
                ->get()
                ->getRowArray()['output_weight_kg'] ?? 0;

            $result[] = [
                'month'    => $monthLabel,
                'input'    => (float) $inputTotal,
                'output'   => (float) $outputTotal,
                'reduction'=> (float) $inputTotal - (float) $outputTotal,
            ];
        }
        return $result;
    }

    /**
     * Drum utilization summary.
     */
    public function getDrumUtilization(): array
    {
        $counts = $this->drumModel->getStatusCounts();
        $total = array_sum($counts);
        $utilization = [];
        foreach ($counts as $status => $count) {
            $utilization[$status] = [
                'count' => $count,
                'pct'   => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }
        return $utilization;
    }

    /**
     * Export yield-by-drum report as CSV.
     */
    public function exportCsv($reportType = 'yield-by-drum')
    {
        $filename = "bmg_{$reportType}_" . date('Ymd_His') . ".csv";

        $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->response->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");

        $output = fopen('php://output', 'w');

        switch ($reportType) {
            case 'yield-by-drum':
                fputcsv($output, ['Drum Code', 'Drum Name', 'Avg Yield %', 'Batch Count']);
                foreach ($this->getYieldByDrum() as $row) {
                    fputcsv($output, [
                        $row['drum_code'],
                        $row['drum_name'],
                        round((float) $row['avg_yield'], 2),
                        $row['batch_count'],
                    ]);
                }
                break;

            case 'duration-by-waste':
                fputcsv($output, ['Waste Type', 'Avg Duration (days)', 'Batch Count']);
                foreach ($this->getDurationByWasteType() as $row) {
                    fputcsv($output, [
                        $row['waste_name'],
                        round((float) $row['avg_duration'], 1),
                        $row['batch_count'],
                    ]);
                }
                break;

            case 'monthly-totals':
                fputcsv($output, ['Month', 'Total Input (kg)', 'Total Output (kg)', 'Mass Reduction (kg)']);
                foreach ($this->getMonthlyTotals() as $row) {
                    fputcsv($output, [
                        $row['month'],
                        $row['input'],
                        $row['output'],
                        $row['reduction'],
                    ]);
                }
                break;

            default:
                fputcsv($output, ['Unknown report type: ' . $reportType]);
                break;
        }

        fclose($output);
        return $this->response;
    }
}