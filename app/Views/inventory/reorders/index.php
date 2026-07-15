<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <h1><?= esc($heading ?? 'Reorder Requests') ?></h1>

    <div class="actions">
        <a href="<?= base_url('inventory/reorders/create') ?>" class="btn btn-primary">+ New Reorder</a>
        <a href="<?= base_url('inventory/reorders/auto-check') ?>" class="btn">Auto-Check All Medicines</a>
        <a href="<?= base_url('inventory/reorders?status=pending') ?>" class="btn btn-sm">Pending</a>
        <a href="<?= base_url('inventory/reorders?status=approved') ?>" class="btn btn-sm">Approved</a>
        <a href="<?= base_url('inventory/reorders?status=ordered') ?>" class="btn btn-sm">Ordered</a>
        <a href="<?= base_url('inventory/reorders?status=received') ?>" class="btn btn-sm">Received</a>
        <a href="<?= base_url('inventory/reorders') ?>" class="btn btn-sm">All</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('info')): ?>
        <div class="alert alert-info"><?= esc(session()->getFlashdata('info')) ?></div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Medicine</th>
                <th>Qty</th>
                <th>Current Stock</th>
                <th>Urgency</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="8" class="empty-state">No reorder requests yet.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td>#<?= esc($r['id']) ?></td>
                        <td><?= esc($r['generic_name'] ?? $r['brand_name'] ?? '-') ?></td>
                        <td><?= esc($r['requested_quantity']) ?> <?= esc($r['unit'] ?? '') ?></td>
                        <td><?= esc($r['current_stock']) ?></td>
                        <td><span class="status-badge urgency-<?= esc($r['urgency']) ?>"><?= esc($r['urgency']) ?></span></td>
                        <td><span class="status-badge status-<?= esc($r['status']) ?>"><?= esc($r['status']) ?></span></td>
                        <td><?= esc(substr($r['created_at'] ?? '', 0, 10)) ?></td>
                        <td><a href="<?= base_url('inventory/reorders/' . $r['id']) ?>" class="btn btn-sm">View</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>