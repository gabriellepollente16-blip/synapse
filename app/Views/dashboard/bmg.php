<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?php
use App\Libraries\BmgDurationCalculator;
?>

<div class="dashboard-stack">

<!-- ============================================================
     KPI TILES - uniform with clinic / counsellor / admin
     ============================================================ -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-drum"></i></div>
        <div class="stat-info">
            <h3><?= esc(array_sum($statusCounts)) ?></h3>
            <p>Total Drums</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-circle-pause"></i></div>
        <div class="stat-info">
            <h3><?= esc($statusCounts['idle'] ?? 0) ?></h3>
            <p>Idle Drums</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-spinner"></i></div>
        <div class="stat-info">
            <h3><?= esc($statusCounts['processing'] ?? 0) ?></h3>
            <p>Processing</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-screwdriver-wrench"></i></div>
        <div class="stat-info">
            <h3><?= esc($statusCounts['maintenance'] ?? 0) ?></h3>
            <p>Maintenance</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-arrow-down"></i></div>
        <div class="stat-info">
            <h3><?= esc(number_format($totalInputLast30, 1)) ?> <small style="font-size: 0.6em; color: var(--gray-500);">kg</small></h3>
            <p>Input &middot; Last 30 Days</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-info">
            <h3><?= esc(number_format($totalOutputLast30, 1)) ?> <small style="font-size: 0.6em; color: var(--gray-500);">kg</small></h3>
            <p>Output &middot; Last 30 Days</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-percent"></i></div>
        <div class="stat-info">
            <h3><?= esc(number_format($avgYield, 1)) ?>%</h3>
            <p>Average Yield</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-info">
            <h3><?= esc(count($idleAlerts)) ?></h3>
            <p>Idle Drum Alerts</p>
        </div>
    </div>
</div>

<!-- ============================================================
     PROCESSING DRUMS — full-width standalone row
     ============================================================ -->
<div class="card processing-drums-card">
        <div class="card-header">
            <i class="fas fa-spinner fa-spin"></i> Processing Drums
            <span class="badge badge-warning" style="margin-left: auto;"><?= count($activeBatches) ?> active</span>
        </div>
        <div class="card-body">
            <?php if (empty($activeBatches)): ?>
                <div class="placeholder-box">
                    <i class="fas fa-drum placeholder-icon"></i>
                    <p class="placeholder-text">No drums currently processing. Add a drum and start a batch to begin composting.</p>
                    <a href="<?= base_url('bmg/drums/create') ?>" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Add a drum
                    </a>
                </div>
            <?php else: ?>
                <div class="drum-card-grid">
                    <?php foreach ($activeBatches as $batch):
                        $days      = (int) ((strtotime('now') - strtotime($batch['start_date'])) / 86400);
                        $ageBadge  = $days > 30 ? 'badge-warning' : 'badge-info';
                        $statusKey = $batch['status']; // 'input' or 'processing'
                        $progress  = BmgDurationCalculator::progressPercent($batch['start_date'], $batch['expected_completion_date'] ?? null);
                        $daysLeft  = BmgDurationCalculator::daysUntilExpected($batch['expected_completion_date'] ?? null);
                        $expFmt    = ! empty($batch['expected_completion_date']) ? date('M j, Y', strtotime($batch['expected_completion_date'])) : '—';
                    ?>
                        <div class="drum-card">
                            <div class="drum-card-head">
                                <div>
                                    <div class="drum-card-code"><?= esc($batch['drum_code']) ?></div>
                                    <div class="drum-card-name"><?= esc($batch['drum_name'] ?? '') ?></div>
                                </div>
                                <span class="status-badge status-<?= esc($statusKey) ?>">
                                    <?= esc(ucfirst($statusKey)) ?>
                                </span>
                            </div>
                            <div class="drum-card-body">
                                <div class="drum-card-row">
                                    <span class="drum-card-label">Batch</span>
                                    <span class="drum-card-value"><?= esc($batch['batch_code']) ?></span>
                                </div>
                                <div class="drum-card-row">
                                    <span class="drum-card-label">Waste</span>
                                    <span class="drum-card-value"><?= esc($batch['waste_name'] ?? '-') ?></span>
                                </div>
                                <div class="drum-card-row">
                                    <span class="drum-card-label">Input</span>
                                    <span class="drum-card-value"><?= esc(number_format((float) $batch['input_weight_kg'], 2)) ?> kg</span>
                                </div>
                                <div class="drum-card-row">
                                    <span class="drum-card-label">Expected Done</span>
                                    <span class="drum-card-value">
                                        <?= esc($expFmt) ?>
                                        <?php if ($daysLeft !== null): ?>
                                            <small style="display:block; font-weight: 500; color: <?= $daysLeft < 0 ? '#b91c1c' : 'var(--gray-500)' ?>;">
                                                <?php if ($daysLeft < 0): ?>
                                                    <i class="fas fa-exclamation-triangle"></i> <?= abs($daysLeft) ?> day<?= abs($daysLeft) === 1 ? '' : 's' ?> overdue
                                                <?php elseif ($daysLeft === 0): ?>
                                                    Due today
                                                <?php else: ?>
                                                    in <?= $daysLeft ?> day<?= $daysLeft === 1 ? '' : 's' ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="drum-card-progress" aria-label="Decomposition progress">
                                    <div class="drum-card-progress-bar" style="width: <?= $progress ?>%;"></div>
                                </div>
                                <div class="drum-card-row drum-card-footer">
                                    <span class="badge <?= $ageBadge ?>">
                                        <?= $days ?> day<?= $days === 1 ? '' : 's' ?> active
                                    </span>
                                    <a href="<?= base_url('bmg/batches/' . $batch['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Open
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<!-- ============================================================
     IDLE DRUM ALERTS — full-width standalone row
     ============================================================ -->
<div class="card">
        <div class="card-header">
            <i class="fas fa-triangle-exclamation"></i> Idle Drum Alerts
            <span class="badge badge-<?= count($idleAlerts) > 0 ? 'warning' : 'success' ?>" style="margin-left: auto;">
                <?= count($idleAlerts) ?>
            </span>
        </div>
        <div class="card-body">
            <?php if (empty($idleAlerts)): ?>
                <div class="placeholder-box">
                    <i class="fas fa-circle-check placeholder-icon" style="color: var(--success);"></i>
                    <p class="placeholder-text">All drums have activity within the last 30 days.</p>
                </div>
            <?php else: ?>
                <ul class="alert-list">
                    <?php foreach ($idleAlerts as $drum): ?>
                        <li class="alert-item">
                            <div class="alert-item-icon"><i class="fas fa-drum"></i></div>
                            <div class="alert-item-body">
                                <div class="alert-item-title"><?= esc($drum['drum_code']) ?> &middot; <?= esc($drum['name']) ?></div>
                                <div class="alert-item-meta">
                                    Last batch: <?= esc($drum['last_batch_date'] ?? 'never') ?>
                                </div>
                            </div>
                            <a href="<?= base_url('bmg/batches/start/' . $drum['id']) ?>" class="btn btn-sm btn-primary">
                                Start batch
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
</div>

<!-- ============================================================
     RECENTLY COMPLETED BATCHES (full-width table)
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-clipboard-check"></i> Recently Completed Batches (Last 30 Days)
        <span class="badge badge-success" style="margin-left: auto;"><?= count($completedBatches) ?></span>
    </div>
    <div class="card-body">
        <?php if (empty($completedBatches)): ?>
            <div class="placeholder-box">
                <i class="fas fa-hourglass placeholder-icon"></i>
                <p class="placeholder-text">No completed batches in the last 30 days.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Batch</th>
                        <th scope="col">Drum</th>
                        <th scope="col">Duration</th>
                        <th scope="col">Yield %</th>
                        <th scope="col">Mass Reduction</th>
                        <th scope="col">Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completedBatches as $batch): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= esc($batch['batch_code']) ?></td>
                            <td><?= esc($batch['drum_code']) ?></td>
                            <td><?= esc($batch['duration_days']) ?> day<?= $batch['duration_days'] == 1 ? '' : 's' ?></td>
                            <td>
                                <span class="badge badge-<?= (float) $batch['yield_percentage'] >= 25 ? 'success' : 'warning' ?>">
                                    <?= esc(number_format((float) $batch['yield_percentage'], 1)) ?>%
                                </span>
                            </td>
                            <td><?= esc(number_format((float) $batch['mass_reduction_pct'], 1)) ?>%</td>
                            <td><?= esc(date('M d, Y', strtotime($batch['completion_date']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     QUICK ACTIONS - uniform with admin dashboard pills
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-bolt"></i> Quick Actions
    </div>
    <div class="card-body">
        <div class="quick-actions-box">
            <p class="quick-actions-label">Jump into the most common BMG workflows</p>
            <div class="quick-action-pills">
                <a href="<?= base_url('bmg/drums') ?>" class="pill pill-teal">
                    <i class="fas fa-drum"></i> Manage Drums
                </a>
                <a href="<?= base_url('bmg/batches') ?>" class="pill pill-blue">
                    <i class="fas fa-layer-group"></i> All Batches
                </a>
                <a href="<?= base_url('bmg/categories') ?>" class="pill pill-purple">
                    <i class="fas fa-tags"></i> Waste Categories
                </a>
                <a href="<?= base_url('bmg/reports') ?>" class="pill pill-orange">
                    <i class="fas fa-chart-line"></i> Reports &amp; Analytics
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     AI-GENERATED FACILITY INSIGHTS (parity with other dashboards)
     ============================================================ -->
<div class="card insight-card">
    <div class="card-header insight-header">
        <i class="fas fa-robot"></i> AI-Generated Facility Operations Insights &mdash; Last 30 Days
    </div>
    <div class="card-body insight-body">
        <p class="insight-narrative placeholder-text">
            No AI summary available yet. Composting efficiency summaries are generated nightly once enough batches have been completed; check back after the next refresh.
        </p>
    </div>
</div>

</div><!-- /.dashboard-stack -->

<style>
    /* ----- Dashboard stack -----
       Every top-level section inside the dashboard uses this wrapper
       so they share a uniform vertical rhythm. */
    .dashboard-stack > * + * {
        margin-top: 1.5rem;
    }

    .alert-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .alert-item {
        display: flex;
        align-items: center;
        gap: 0.875rem;
        padding: 0.75rem 1rem;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 0.625rem;
        border-left: 4px solid #f97316;
    }
    .alert-item-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #f97316;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.85rem;
    }
    .alert-item-body { flex: 1; min-width: 0; }
    .alert-item-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 0.15rem;
    }
    .alert-item-meta { font-size: 0.75rem; color: var(--gray-500); }

    /* ----- Processing drums card grid ----- */
    .processing-drums-card { border-left: 4px solid #f97316; }
    .drum-card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 0.75rem;
    }
    .drum-card {
        background: #fff;
        border: 1px solid var(--gray-200);
        border-left: 4px solid #f97316;
        border-radius: 0.625rem;
        padding: 0;
        overflow: hidden;
        transition: box-shadow 0.15s ease, transform 0.15s ease;
    }
    .drum-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
    }
    .drum-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.75rem 0.85rem;
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-100);
    }
    .drum-card-code {
        font-family: var(--font-mono, 'JetBrains Mono', monospace);
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--gray-900);
        letter-spacing: 0.02em;
    }
    .drum-card-name { font-size: 0.75rem; color: var(--gray-500); margin-top: 0.1rem; }
    .drum-card-body { padding: 0.75rem 0.85rem; }
    .drum-card-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.25rem 0;
        font-size: 0.82rem;
    }
    .drum-card-label { color: var(--gray-500); font-weight: 500; }
    .drum-card-value { color: var(--gray-800); font-weight: 600; text-align: right; }
    .drum-card-progress {
        margin: 0.55rem 0 0.35rem;
        height: 6px;
        background: var(--gray-100);
        border-radius: 999px;
        overflow: hidden;
    }
    .drum-card-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #fb923c 0%, #f97316 100%);
        border-radius: 999px;
        transition: width 0.4s ease;
    }
    .drum-card-footer {
        margin-top: 0.4rem;
        padding-top: 0.5rem;
        border-top: 1px dashed var(--gray-100);
    }

    /* Insight card spacing. */
    .insight-card { margin-top: 1.5rem; }
</style>
<?= $this->endSection() ?>