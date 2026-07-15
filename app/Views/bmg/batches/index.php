<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        'Batches',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'Composting Batches',
        'subtitle' => 'Track every batch across all drums — input, processing, and harvested.',
        'actions'  =>
            '<a href="' . base_url('bmg/drums') . '" class="btn btn-primary">'
            . '<i class="fas fa-play"></i> Pick a Drum to Start</a>',
    ]) ?>

    <?= view('components/flash') ?>

    <div class="section">
        <form method="get" action="<?= base_url('bmg/batches') ?>" class="list-toolbar">
            <a href="<?= base_url('bmg/batches') ?>" class="btn btn-sm <?= empty($filter ?? '') ? 'btn-primary' : '' ?>">All</a>
            <a href="<?= base_url('bmg/batches?status=processing') ?>" class="btn btn-sm <?= ($filter ?? '') === 'processing' ? 'btn-primary' : '' ?>">Active</a>
            <a href="<?= base_url('bmg/batches?status=completed') ?>" class="btn btn-sm <?= ($filter ?? '') === 'completed' ? 'btn-primary' : '' ?>">Completed</a>
            <a href="<?= base_url('bmg/batches?status=cancelled') ?>" class="btn btn-sm <?= ($filter ?? '') === 'cancelled' ? 'btn-primary' : '' ?>">Cancelled</a>
        </form>
    </div>

    <div class="section">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>Drum</th>
                    <th>Waste</th>
                    <th>Input (kg)</th>
                    <th>Yield %</th>
                    <th>Started</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($batches)): ?>
                    <tr><td colspan="7" class="empty-state">No batches found.</td></tr>
                <?php else: ?>
                    <?php foreach ($batches as $b): ?>
                        <tr>
                            <td><a href="<?= base_url('bmg/batches/' . $b['id']) ?>"><?= esc($b['batch_code']) ?></a></td>
                            <td><?= esc($b['drum_code']) ?></td>
                            <td><?= esc($b['waste_name'] ?? '—') ?></td>
                            <td><?= number_format((float) $b['input_weight_kg'], 2) ?></td>
                            <td><?= $b['yield_percentage'] !== null ? number_format((float) $b['yield_percentage'], 1) . '%' : '—' ?></td>
                            <td><?= esc($b['start_date'] ?? '—') ?></td>
                            <td><span class="status-badge status-<?= esc($b['status']) ?>"><?= esc($b['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
