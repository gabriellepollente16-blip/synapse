<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        ['label' => 'Drums',               'href' => base_url('bmg/drums')],
        $drum['drum_code'],
    ],
]) ?>

<div class="container">

    <div class="page-header">
        <div>
            <h1><?= esc($drum['drum_code']) ?> <span style="color: var(--gray-500); font-weight: 500; font-size: 1rem;">— <?= esc($drum['name']) ?></span></h1>
            <p class="subtitle">
                <span class="status-badge status-<?= esc($drum['current_status']) ?>" style="vertical-align: middle;"><?= esc($drum['current_status']) ?></span>
                <span style="margin-left: 0.5rem;">Capacity <?= number_format((float) $drum['capacity_kg'], 1) ?> kg</span>
                <?php if (! empty($drum['location'])): ?>
                    <span style="margin-left: 0.5rem;">· <?= esc($drum['location']) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="actions">
            <a href="<?= base_url('bmg/drums/edit/' . $drum['id']) ?>" class="btn">
                <i class="fas fa-pen"></i> Edit
            </a>
            <a href="<?= base_url('bmg/drums') ?>" class="btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?= view('components/flash') ?>

    <!-- ============================================================
         Two-column facts: identity card + location card
         ============================================================ -->
    <div class="section info-grid">
        <div class="info-card">
            <div class="info-card-title"><i class="fas fa-drum"></i> Drum Identity</div>
            <dl>
                <dt>Drum Code</dt>     <dd><?= esc($drum['drum_code']) ?></dd>
                <dt>Name</dt>          <dd><?= esc($drum['name']) ?></dd>
                <dt>Status</dt>        <dd><span class="status-badge status-<?= esc($drum['current_status']) ?>"><?= esc($drum['current_status']) ?></span></dd>
                <dt>Capacity</dt>      <dd><?= number_format((float) $drum['capacity_kg'], 2) ?> kg</dd>
                <dt>Archived</dt>      <dd><?= ! empty($drum['deleted_at']) ? 'Yes' : 'No' ?></dd>
            </dl>
        </div>
        <div class="info-card">
            <div class="info-card-title"><i class="fas fa-map-marker-alt"></i> Location &amp; Installation</div>
            <dl>
                <dt>Location</dt>          <dd><?= esc($drum['location'] ?? '—') ?></dd>
                <dt>Installed</dt>         <dd><?= esc($drum['installation_date'] ?? '—') ?></dd>
                <dt>Created</dt>           <dd><?= esc($drum['created_at'] ?? '—') ?></dd>
                <dt>Last updated</dt>      <dd><?= esc($drum['updated_at'] ?? '—') ?></dd>
            </dl>
        </div>
    </div>

    <!-- ============================================================
         Quick Actions — every status transition + delete (with confirm)
         ============================================================ -->
    <div class="section">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bolt"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">

                    <?php if (empty($activeBatch)): ?>
                        <a href="<?= base_url('bmg/batches/startOnDrum/' . $drum['id']) ?>" class="quick-action-tile success">
                            <i class="fas fa-play"></i> Start Batch
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('bmg/batches/' . $activeBatch['id']) ?>" class="quick-action-tile">
                            <i class="fas fa-eye"></i> View Active Batch
                        </a>
                    <?php endif; ?>

                    <form method="post" action="<?= base_url('bmg/drums/complete-and-idle/' . $drum['id']) ?>" style="display: contents;">
                        <?= csrf_field() ?>
                        <button type="submit" class="quick-action-tile success"
                                onclick="return confirm('Mark the current batch as complete and return the drum to idle? Make sure the harvest weight is recorded first.');">
                            <i class="fas fa-check-double"></i> Complete &amp; Idle
                        </button>
                    </form>

                    <form method="post" action="<?= base_url('bmg/drums/mark-processing/' . $drum['id']) ?>" style="display: contents;">
                        <?= csrf_field() ?>
                        <button type="submit" class="quick-action-tile"
                                onclick="return confirm('Set this drum to processing? Use this only when starting a manual run without a tracked batch.');">
                            <i class="fas fa-spinner"></i> Mark Processing
                        </button>
                    </form>

                    <form method="post" action="<?= base_url('bmg/drums/mark-idle/' . $drum['id']) ?>" style="display: contents;">
                        <?= csrf_field() ?>
                        <button type="submit" class="quick-action-tile"
                                onclick="return confirm('Set this drum to idle? If a batch is in progress it will become untracked.');">
                            <i class="fas fa-pause"></i> Set Idle
                        </button>
                    </form>

                    <form method="post" action="<?= base_url('bmg/drums/mark-maintenance/' . $drum['id']) ?>" style="display: contents;">
                        <?= csrf_field() ?>
                        <button type="submit" class="quick-action-tile warn">
                            <i class="fas fa-screwdriver-wrench"></i> Maintenance
                        </button>
                    </form>

                    <?php if (empty($drum['deleted_at'])): ?>
                    <form method="post" action="<?= base_url('bmg/drums/archive/' . $drum['id']) ?>" style="display: contents;">
                        <?= csrf_field() ?>
                        <button type="submit" class="quick-action-tile warn"
                                onclick="return confirm('Archive this drum? It will be hidden from the active list but batch history is preserved.');">
                            <i class="fas fa-box-archive"></i> Archive
                        </button>
                    </form>
                    <?php endif; ?>

                    <form method="post" action="<?= base_url('bmg/drums/delete/' . $drum['id']) ?>" style="display: contents;">
                        <?= csrf_field() ?>
                        <button type="submit" class="quick-action-tile danger"
                                onclick="return confirm('PERMANENTLY delete this drum? This cannot be undone. The drum must have no batch records on file.');">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         Active Batch callout
         ============================================================ -->
    <div class="section">
        <?php if (! empty($activeBatch)): ?>
            <?php
                $b        = $activeBatch;
                $days     = (int) ((time() - strtotime($b['start_date'])) / 86400);
                $expFmt   = ! empty($b['expected_completion_date']) ? date('M j, Y', strtotime($b['expected_completion_date'])) : '—';
            ?>
            <div class="active-batch-callout">
                <div class="icon"><i class="fas fa-layer-group"></i></div>
                <div class="meta">
                    <div class="meta-title">
                        <?= esc($b['batch_code']) ?>
                        <span class="status-badge status-<?= esc($b['status']) ?>" style="margin-left: 0.4rem;"><?= esc($b['status']) ?></span>
                    </div>
                    <div class="meta-sub">
                        Input: <?= number_format((float) $b['input_weight_kg'], 2) ?> kg
                        · Day <?= $days ?> of <?= (int) ($b['expected_duration_days'] ?? 45) ?>
                        · Expected done: <strong><?= esc($expFmt) ?></strong>
                    </div>
                </div>
                <a href="<?= base_url('bmg/batches/' . $b['id']) ?>" class="btn btn-primary">
                    Open batch <i class="fas fa-arrow-right" style="margin-left: 0.25rem;"></i>
                </a>
            </div>
        <?php else: ?>
            <div class="empty-batch-callout">
                <i class="fas fa-circle-info"></i> No active batch. <a href="<?= base_url('bmg/batches/startOnDrum/' . $drum['id']) ?>" style="color: var(--primary-600); font-weight: 500;">Start a new batch</a> to begin composting.
            </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================
         Notes
         ============================================================ -->
    <div class="section">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-sticky-note"></i> Notes
            </div>
            <div class="card-body">
                <div class="notes-block <?= empty($drum['notes']) ? 'empty' : '' ?>">
                    <?= empty($drum['notes']) ? 'No notes recorded for this drum.' : esc($drum['notes']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         Recent Batches
         ============================================================ -->
    <div class="section">
        <h2 class="section-title"><i class="fas fa-clock-rotate-left"></i> Recent Batches (last 10)</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>Status</th>
                    <th>Input (kg)</th>
                    <th>Output (kg)</th>
                    <th>Yield %</th>
                    <th>Started</th>
                    <th>Completed</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentBatches)): ?>
                    <tr><td colspan="7" class="empty-state">No batches yet for this drum.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentBatches as $b): ?>
                        <tr>
                            <td><a href="<?= base_url('bmg/batches/' . $b['id']) ?>"><?= esc($b['batch_code']) ?></a></td>
                            <td><span class="status-badge status-<?= esc($b['status']) ?>"><?= esc($b['status']) ?></span></td>
                            <td><?= number_format((float) $b['input_weight_kg'], 2) ?></td>
                            <td><?= $b['output_weight_kg'] !== null ? number_format((float) $b['output_weight_kg'], 2) : '—' ?></td>
                            <td><?= $b['yield_percentage'] !== null ? number_format((float) $b['yield_percentage'], 1) . '%' : '—' ?></td>
                            <td><?= esc($b['start_date'] ?? '—') ?></td>
                            <td><?= esc($b['completion_date'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
