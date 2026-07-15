<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        'Reports & Analytics',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'BMG Analytics & Reports',
        'subtitle' => 'Yield by drum, duration by waste type, and monthly totals.',
        'actions'  =>
            '<a href="' . base_url('bmg/reports/export-csv/yield-by-drum') . '" class="btn btn-sm">'
            . '<i class="fas fa-file-csv"></i> Yield by Drum</a>'
            . '<a href="' . base_url('bmg/reports/export-csv/duration-by-waste') . '" class="btn btn-sm">'
            . '<i class="fas fa-file-csv"></i> Duration by Waste</a>'
            . '<a href="' . base_url('bmg/reports/export-csv/monthly-totals') . '" class="btn btn-sm">'
            . '<i class="fas fa-file-csv"></i> Monthly Totals</a>',
    ]) ?>

    <?= view('components/flash') ?>

    <!-- Yield by Drum -->
    <div class="section">
        <h2 class="section-title"><i class="fas fa-drum"></i> Yield by Drum (last 90 days)</h2>
        <table class="data-table">
            <thead><tr><th>Drum Code</th><th>Drum Name</th><th>Avg Yield %</th><th>Batch Count</th></tr></thead>
            <tbody>
                <?php if (empty($yieldByDrum)): ?>
                    <tr><td colspan="4" class="empty-state">No completed batches yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($yieldByDrum as $r): ?>
                        <tr>
                            <td><strong><?= esc($r['drum_code']) ?></strong></td>
                            <td><?= esc($r['drum_name']) ?></td>
                            <td><?= number_format((float) $r['avg_yield'], 1) ?>%</td>
                            <td><?= esc($r['batch_count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Duration by Waste -->
    <div class="section">
        <h2 class="section-title"><i class="fas fa-clock"></i> Avg. Duration by Waste Type (last 90 days)</h2>
        <table class="data-table">
            <thead><tr><th>Waste Type</th><th>Avg Duration (days)</th><th>Batch Count</th></tr></thead>
            <tbody>
                <?php if (empty($durationByWaste)): ?>
                    <tr><td colspan="3" class="empty-state">No completed batches yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($durationByWaste as $r): ?>
                        <tr>
                            <td><?= esc($r['waste_name']) ?></td>
                            <td><?= number_format((float) $r['avg_duration'], 1) ?></td>
                            <td><?= esc($r['batch_count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Monthly Totals -->
    <div class="section">
        <h2 class="section-title"><i class="fas fa-calendar"></i> Monthly Totals (last 6 months)</h2>
        <table class="data-table">
            <thead><tr><th>Month</th><th>Total Input (kg)</th><th>Total Output (kg)</th><th>Mass Reduction (kg)</th></tr></thead>
            <tbody>
                <?php foreach ($monthlyTotals as $r): ?>
                    <tr>
                        <td><?= esc($r['month']) ?></td>
                        <td><?= number_format($r['input'], 2) ?></td>
                        <td><?= number_format($r['output'], 2) ?></td>
                        <td><?= number_format($r['reduction'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Drum Utilization -->
    <div class="section">
        <h2 class="section-title"><i class="fas fa-chart-pie"></i> Drum Utilization</h2>
        <table class="data-table">
            <thead><tr><th>Status</th><th>Count</th><th>%</th></tr></thead>
            <tbody>
                <?php foreach ($drumUtilization as $status => $data): ?>
                    <tr>
                        <td><span class="status-badge status-<?= esc($status) ?>"><?= esc(ucfirst($status)) ?></span></td>
                        <td><?= $data['count'] ?></td>
                        <td><?= $data['pct'] ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
