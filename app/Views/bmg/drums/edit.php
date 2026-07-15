<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        ['label' => 'Drums',               'href' => base_url('bmg/drums')],
        ['label' => $drum['drum_code'],    'href' => base_url('bmg/drums/' . $drum['id'])],
        'Edit',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'Edit ' . $drum['drum_code'],
        'subtitle' => 'Update drum name, location, capacity, status, or notes.',
    ]) ?>

    <?= view('components/flash') ?>

    <div class="section">
        <div class="card">
            <div class="card-body">
                <form action="<?= base_url('bmg/drums/update/' . $drum['id']) ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Drum Code</label>
                            <input type="text" value="<?= esc($drum['drum_code']) ?>" disabled>
                            <small>Drum code cannot be changed.</small>
                        </div>
                        <div class="form-group">
                            <label for="name">Name *</label>
                            <input type="text" name="name" id="name" required maxlength="150" value="<?= esc($drum['name']) ?>" class="syn-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" value="<?= esc($drum['location'] ?? '') ?>" class="syn-input">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="capacity_kg">Capacity (kg) *</label>
                            <input type="number" name="capacity_kg" id="capacity_kg" required step="0.01" min="1" value="<?= esc($drum['capacity_kg']) ?>" class="syn-input">
                        </div>
                        <div class="form-group">
                            <label for="current_status">Status</label>
                            <select name="current_status" id="current_status" class="syn-select">
                                <option value="idle"        <?= $drum['current_status'] === 'idle'        ? 'selected' : '' ?>>Idle</option>
                                <option value="processing"  <?= $drum['current_status'] === 'processing'  ? 'selected' : '' ?>>Processing</option>
                                <option value="maintenance" <?= $drum['current_status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            </select>
                            <small>For full lifecycle control, use the Quick Actions on the drum's detail page.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="syn-input"><?= esc($drum['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="<?= base_url('bmg/drums/' . $drum['id']) ?>" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>