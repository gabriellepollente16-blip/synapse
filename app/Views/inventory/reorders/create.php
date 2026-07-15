<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <h1><?= esc($heading ?? 'New Reorder Request') ?></h1>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <ul><?php foreach (session()->getFlashdata('errors') as $err): ?><li><?= esc($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('inventory/reorders/store') ?>" method="post" class="form-card">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="medicine_id">Medicine *</label>
            <select name="medicine_id" id="medicine_id" required>
                <option value="">-- Select medicine --</option>
                <?php foreach ($medicines as $m): ?>
                    <option value="<?= esc($m['id']) ?>">
                        <?= esc($m['generic_name']) ?> / <?= esc($m['brand_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="requested_quantity">Requested Quantity *</label>
                <input type="number" name="requested_quantity" id="requested_quantity" required min="1" value="50">
            </div>
            <div class="form-group">
                <label for="urgency">Urgency *</label>
                <select name="urgency" id="urgency" required>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="procurement_notes">Notes</label>
            <textarea name="procurement_notes" id="procurement_notes" rows="2"></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Reorder</button>
            <a href="<?= base_url('inventory/reorders') ?>" class="btn">Cancel</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>