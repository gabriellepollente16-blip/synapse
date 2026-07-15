<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?php
use App\Libraries\BmgDurationCalculator;
?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        ['label' => 'Batches',             'href' => base_url('bmg/batches')],
        $batch['batch_code'],
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => $batch['batch_code'],
        'subtitle' => 'On drum ' . $batch['drum_code'] . ' · Started ' . ($batch['start_date'] ?? '—'),
    ]) ?>

    <?= view('components/flash') ?>

    <!-- ============================================================
         Estimated Completion callout (shown for active batches)
         ============================================================ -->
    <?php if (in_array($batch['status'], ['input', 'processing']) && ! empty($batch['expected_completion_date'])):
        $daysLeft = BmgDurationCalculator::daysUntilExpected($batch['expected_completion_date']);
        $progress = BmgDurationCalculator::progressPercent($batch['start_date'], $batch['expected_completion_date']);
        $daysWord = $daysLeft === 1 ? 'day' : 'days';
        $overdue  = $daysLeft !== null && $daysLeft < 0;
    ?>
    <div class="section">
        <div class="estimate-callout <?= $overdue ? 'overdue' : '' ?>">
            <div class="estimate-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="estimate-body">
                <div class="estimate-title">Estimated Completion</div>
                <div class="estimate-date"><?= esc(date('l, F j, Y', strtotime($batch['expected_completion_date']))) ?></div>
                <div class="estimate-sub">
                    <?php if ($overdue): ?>
                        <?= abs((int) $daysLeft) ?> <?= $daysWord ?> overdue — please record output or update the batch status.
                    <?php elseif ($daysLeft === 0): ?>
                        <strong>Today is the expected completion date.</strong>
                    <?php else: ?>
                        <?= (int) $daysLeft ?> <?= $daysWord ?> remaining · about <?= (int) $batch['expected_duration_days'] ?> day total
                    <?php endif; ?>
                </div>
            </div>
            <div class="estimate-progress" aria-label="Decomposition progress">
                <div class="estimate-progress-bar" style="width: <?= $progress ?>%;"></div>
            </div>
            <div class="estimate-progress-label"><?= $progress ?>%</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================
         Identity grid + action grid
         ============================================================ -->
    <div class="section info-grid">
        <div class="info-card">
            <div class="info-card-title"><i class="fas fa-layer-group"></i> Batch Summary</div>
            <dl>
                <dt>Batch</dt>        <dd><?= esc($batch['batch_code']) ?></dd>
                <dt>Drum</dt>         <dd><?= esc($batch['drum_code']) ?> &mdash; <?= esc($batch['drum_name']) ?></dd>
                <dt>Waste</dt>        <dd><?= esc($batch['waste_name']) ?></dd>
                <dt>Status</dt>       <dd><span class="status-badge status-<?= esc($batch['status']) ?>"><?= esc($batch['status']) ?></span></dd>
            </dl>
        </div>
        <div class="info-card">
            <div class="info-card-title"><i class="fas fa-weight-hanging"></i> Weights &amp; Yield</div>
            <dl>
                <dt>Input Total</dt>      <dd><?= number_format((float) $batch['input_weight_kg'], 2) ?> kg</dd>
                <dt>Output Total</dt>     <dd><?= $batch['output_weight_kg'] !== null ? number_format((float) $batch['output_weight_kg'], 2) . ' kg' : 'Not yet recorded' ?></dd>
                <dt>Yield %</dt>          <dd><?= $batch['yield_percentage'] !== null ? number_format((float) $batch['yield_percentage'], 1) . '%' : '—' ?></dd>
                <dt>Mass Reduction</dt>   <dd><?= $batch['mass_reduction_pct'] !== null ? number_format((float) $batch['mass_reduction_pct'], 1) . '%' : '—' ?></dd>
                <dt>Duration</dt>         <dd><?= $batch['duration_days'] !== null ? $batch['duration_days'] . ' days' : '—' ?></dd>
            </dl>
        </div>
    </div>

    <?php if (in_array($batch['status'], ['input', 'processing'])): ?>
    <div class="section">
        <div class="card">
            <div class="card-header"><i class="fas fa-bolt"></i> Quick Actions</div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <a href="<?= base_url('bmg/batches/' . $batch['id'] . '/inputs/create') ?>" class="quick-action-tile">
                        <i class="fas fa-plus"></i> Record Input
                    </a>
                    <a href="<?= base_url('bmg/batches/' . $batch['id'] . '/process-logs/create') ?>" class="quick-action-tile">
                        <i class="fas fa-clipboard-list"></i> Add Observation
                    </a>
                    <a href="<?= base_url('bmg/batches/' . $batch['id'] . '/output/create') ?>" class="quick-action-tile success">
                        <i class="fas fa-trophy"></i> Record Output
                    </a>
                    <form method="post" action="<?= base_url('bmg/batches/' . $batch['id'] . '/mark-completed') ?>" style="display: contents;">
                        <?= csrf_field() ?>
                        <button type="submit" class="quick-action-tile warn"
                                onclick="return confirm('Mark this batch as completed? It can still be opened to record the output weight.');">
                            <i class="fas fa-check-double"></i> Mark Completed
                        </button>
                    </form>
                    <form method="post" action="<?= base_url('bmg/batches/' . $batch['id'] . '/cancel') ?>" style="display: contents;">
                        <?= csrf_field() ?>
                        <button type="submit" class="quick-action-tile danger"
                                onclick="return confirm('Cancel this batch? This cannot be undone.');">
                            <i class="fas fa-xmark"></i> Cancel Batch
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================
         Inputs
         ============================================================ -->
    <div class="section">
        <h2 class="section-title"><i class="fas fa-arrow-down"></i> Inputs (<?= count($inputs) ?>)</h2>
        <table class="data-table">
            <thead><tr><th>Weight (kg)</th><th>Recorded At</th><th>Notes</th></tr></thead>
            <tbody>
                <?php if (empty($inputs)): ?>
                    <tr><td colspan="3" class="empty-state">No inputs recorded.</td></tr>
                <?php else: ?>
                    <?php foreach ($inputs as $i): ?>
                        <tr>
                            <td><?= number_format((float) $i['weight_kg'], 2) ?></td>
                            <td><?= esc($i['recorded_at']) ?></td>
                            <td><?= esc($i['notes'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ============================================================
         Process Logs
         ============================================================ -->
    <div class="section">
        <h2 class="section-title"><i class="fas fa-clipboard-list"></i> Process Logs (<?= count($processLogs) ?>)</h2>
        <table class="data-table">
            <thead><tr><th>Date</th><th>Temp (°C)</th><th>Moisture</th><th>Observation</th></tr></thead>
            <tbody>
                <?php if (empty($processLogs)): ?>
                    <tr><td colspan="4" class="empty-state">No observations logged.</td></tr>
                <?php else: ?>
                    <?php foreach ($processLogs as $p): ?>
                        <tr>
                            <td><?= esc($p['log_date']) ?></td>
                            <td><?= $p['temperature_celsius'] !== null ? number_format((float) $p['temperature_celsius'], 1) : '—' ?></td>
                            <td><?= esc($p['moisture_level'] ?? '—') ?></td>
                            <td><?= esc($p['observation_note'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ============================================================
         Outputs
         ============================================================ -->
    <div class="section">
        <h2 class="section-title"><i class="fas fa-trophy"></i> Outputs (<?= count($outputs) ?>)</h2>
        <table class="data-table">
            <thead><tr><th>Output (kg)</th><th>Quality</th><th>Harvest Date</th></tr></thead>
            <tbody>
                <?php if (empty($outputs)): ?>
                    <tr><td colspan="3" class="empty-state">No output recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($outputs as $o): ?>
                        <tr>
                            <td><?= number_format((float) $o['output_weight_kg'], 2) ?></td>
                            <td><?= esc($o['quality_grade'] ?? '—') ?></td>
                            <td><?= esc($o['harvest_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <a href="<?= base_url('bmg/drums/' . $batch['drum_id']) ?>" class="btn">
            <i class="fas fa-arrow-left"></i> Back to drum
        </a>
        <a href="<?= base_url('bmg/batches') ?>" class="btn">
            <i class="fas fa-list"></i> All Batches
        </a>
    </div>

</div>

<style>
    .estimate-callout {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, #f0f9ff 0%, #ecfeff 100%);
        border: 1px solid #bae6fd;
        border-left: 4px solid #0ea5e9;
        border-radius: 0.625rem;
        flex-wrap: wrap;
    }
    .estimate-callout.overdue {
        background: linear-gradient(135deg, #fef2f2 0%, #fff7ed 100%);
        border-color: #fecaca;
        border-left-color: #ef4444;
    }
    .estimate-callout .estimate-icon {
        width: 44px; height: 44px;
        border-radius: 50%;
        background: #0ea5e9;
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .estimate-callout.overdue .estimate-icon { background: #ef4444; }
    .estimate-callout .estimate-body { flex: 1; min-width: 0; }
    .estimate-callout .estimate-title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #0369a1;
        margin-bottom: 0.2rem;
    }
    .estimate-callout.overdue .estimate-title { color: #b91c1c; }
    .estimate-callout .estimate-date {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.2rem;
    }
    .estimate-callout .estimate-sub {
        font-size: 0.8rem;
        color: var(--gray-500);
    }
    .estimate-callout.overdue .estimate-sub { color: #b91c1c; font-weight: 500; }
    .estimate-progress {
        width: 180px;
        height: 8px;
        background: rgba(255, 255, 255, 0.6);
        border-radius: 999px;
        overflow: hidden;
        flex-shrink: 0;
    }
    .estimate-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #38bdf8 0%, #0ea5e9 100%);
        border-radius: 999px;
        transition: width 0.4s ease;
    }
    .estimate-callout.overdue .estimate-progress-bar {
        background: linear-gradient(90deg, #fb923c 0%, #ef4444 100%);
    }
    .estimate-progress-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #0369a1;
        min-width: 40px;
        text-align: right;
    }
    .estimate-callout.overdue .estimate-progress-label { color: #b91c1c; }
</style>

<?= $this->endSection() ?>
