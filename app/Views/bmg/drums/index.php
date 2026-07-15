<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        'Drums',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'BMG Drums',
        'subtitle' => 'Manage composting drums, track their status, and filter the list to find what you need.',
        'actions'  =>
            '<a href="' . base_url('bmg/drums/create') . '" class="btn btn-primary">'
            . '<i class="fas fa-plus"></i> New Drum</a>'
            . '<a href="' . base_url('bmg/batches') . '" class="btn">All Batches</a>'
            . '<a href="' . base_url('bmg/reports') . '" class="btn">Reports</a>',
    ]) ?>

    <?= view('components/flash') ?>

    <!-- ============================================================
         KPI tiles
         ============================================================ -->
    <div class="section kpi-grid">
        <div class="kpi-tile">
            <div class="kpi-label">Total Drums</div>
            <div class="kpi-value"><?= array_sum($counts) ?></div>
        </div>
        <div class="kpi-tile idle">
            <div class="kpi-label">Idle</div>
            <div class="kpi-value"><?= $counts['idle'] ?? 0 ?></div>
        </div>
        <div class="kpi-tile processing">
            <div class="kpi-label">Processing</div>
            <div class="kpi-value"><?= $counts['processing'] ?? 0 ?></div>
        </div>
        <div class="kpi-tile maintenance">
            <div class="kpi-label">Maintenance</div>
            <div class="kpi-value"><?= $counts['maintenance'] ?? 0 ?></div>
        </div>
    </div>

    <!-- ============================================================
         Search + filter toolbar
         ============================================================ -->
    <form method="get" action="<?= base_url('bmg/drums') ?>" class="list-toolbar" role="search">
        <input type="search"
               name="q"
               value="<?= esc($search ?? '') ?>"
               placeholder="Search by code, name, or location…"
               class="toolbar-search syn-input"
               aria-label="Search drums">
        <select name="status" class="toolbar-filter syn-select" aria-label="Filter by status">
            <option value="">All statuses</option>
            <option value="idle"        <?= ($filter ?? '') === 'idle'        ? 'selected' : '' ?>>Idle</option>
            <option value="processing"  <?= ($filter ?? '') === 'processing'  ? 'selected' : '' ?>>Processing</option>
            <option value="maintenance" <?= ($filter ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
            <option value="archived"    <?= ($filter ?? '') === 'archived'    ? 'selected' : '' ?>>Archived</option>
        </select>
        <button class="btn btn-primary" type="submit">
            <i class="fas fa-filter"></i> Filter
        </button>
        <?php if (($search ?? '') !== '' || ($filter ?? '') !== ''): ?>
            <a href="<?= base_url('bmg/drums') ?>" class="btn">
                <i class="fas fa-xmark"></i> Reset
            </a>
        <?php endif; ?>
    </form>

    <!-- ============================================================
         Drums table
         ============================================================ -->
    <div class="section">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Drum Code</th>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($drums)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <?php if (($search ?? '') !== '' || ($filter ?? '') !== ''): ?>
                                No drums match the current filter.
                                <a href="<?= base_url('bmg/drums') ?>">Clear filter</a>.
                            <?php else: ?>
                                No drums yet. <a href="<?= base_url('bmg/drums/create') ?>">Add the first one</a>.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($drums as $drum): ?>
                        <tr>
                            <td><strong><?= esc($drum['drum_code']) ?></strong></td>
                            <td><?= esc($drum['name']) ?></td>
                            <td><?= esc($drum['location'] ?? '—') ?></td>
                            <td><?= number_format((float) $drum['capacity_kg'], 1) ?> kg</td>
                            <td><span class="status-badge status-<?= esc($drum['current_status']) ?>"><?= esc($drum['current_status']) ?></span></td>
                            <td class="text-right">
                                <a href="<?= base_url('bmg/drums/' . $drum['id']) ?>" class="btn btn-sm">View</a>
                                <a href="<?= base_url('bmg/drums/edit/' . $drum['id']) ?>" class="btn btn-sm">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
