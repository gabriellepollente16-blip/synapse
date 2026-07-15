<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <h1><?= esc($heading ?? 'Reorder Request') ?></h1>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card">
        <table class="info-table">
            <tr><th>Medicine</th><td><?= esc($reorder['generic_name'] ?? '') ?> / <?= esc($reorder['brand_name'] ?? '') ?></td></tr>
            <tr><th>Requested Quantity</th><td><?= esc($reorder['requested_quantity']) ?> <?= esc($reorder['unit'] ?? '') ?></td></tr>
            <tr><th>Current Stock (at request)</th><td><?= esc($reorder['current_stock']) ?></td></tr>
            <tr><th>Current Stock (now)</th><td><?= esc($current_stock) ?></td></tr>
            <tr><th>Reorder Level</th><td><?= esc($reorder['reorder_level']) ?></td></tr>
            <tr><th>Urgency</th><td><span class="status-badge urgency-<?= esc($reorder['urgency']) ?>"><?= esc($reorder['urgency']) ?></span></td></tr>
            <tr><th>Status</th><td><span class="status-badge status-<?= esc($reorder['status']) ?>"><?= esc($reorder['status']) ?></span></td></tr>
            <tr><th>Created</th><td><?= esc($reorder['created_at']) ?></td></tr>
            <?php if ($reorder['order_date']): ?>
                <tr><th>Order Date</th><td><?= esc($reorder['order_date']) ?></td></tr>
            <?php endif; ?>
            <?php if ($reorder['actual_delivery_date']): ?>
                <tr><th>Delivery Date</th><td><?= esc($reorder['actual_delivery_date']) ?></td></tr>
            <?php endif; ?>
            <?php if ($reorder['procurement_notes']): ?>
                <tr><th>Notes</th><td><?= esc($reorder['procurement_notes']) ?></td></tr>
            <?php endif; ?>
        </table>

        <div class="form-actions">
            <?php if ($reorder['status'] === 'pending'): ?>
                <form action="<?= base_url('inventory/reorders/' . $reorder['id'] . '/approve') ?>" method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">Approve</button>
                </form>
            <?php endif; ?>
            <?php if ($reorder['status'] === 'approved'): ?>
                <form action="<?= base_url('inventory/reorders/' . $reorder['id'] . '/order') ?>" method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">Mark as Ordered</button>
                </form>
            <?php endif; ?>
            <?php if ($reorder['status'] === 'ordered'): ?>
                <form action="<?= base_url('inventory/reorders/' . $reorder['id'] . '/receive') ?>" method="post" style="display:inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary">Mark as Received</button>
                </form>
            <?php endif; ?>
            <?php if (!in_array($reorder['status'], ['received', 'cancelled'])): ?>
                <form action="<?= base_url('inventory/reorders/' . $reorder['id'] . '/cancel') ?>" method="post" style="display:inline"
                      onsubmit="return confirm('Cancel this reorder?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn">Cancel</button>
                </form>
            <?php endif; ?>
            <a href="<?= base_url('inventory/reorders') ?>" class="btn">Back</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>