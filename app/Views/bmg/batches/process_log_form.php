<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        ['label' => 'Batches',             'href' => base_url('bmg/batches')],
        ['label' => $batch['batch_code'],  'href' => base_url('bmg/batches/' . $batch['id'])],
        'Add Observation',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'Add Observation',
        'subtitle' => 'Record temperature, moisture, and free-text notes for batch ' . $batch['batch_code'],
    ]) ?>

    <?= view('components/flash') ?>

    <div class="section">
        <div class="card">
            <div class="card-body">
                <form action="<?= base_url('bmg/batches/' . $batch['id'] . '/process-logs/store') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="observation_note">Observation</label>
                        <textarea name="observation_note" id="observation_note" rows="3" placeholder="e.g., Drum turned 3 times today. Material is darkening." class="syn-input"><?= esc(old('observation_note', $observation['observation_note'] ?? '')) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="temperature_celsius">Temperature (°C)</label>
                            <input type="number" name="temperature_celsius" id="temperature_celsius" step="0.1" placeholder="e.g., 35.5" value="<?= esc(old('temperature_celsius', $observation['temperature_celsius'] ?? '')) ?>" class="syn-input">
                        </div>
                        <div class="form-group">
                            <label for="moisture_level">Moisture Level</label>
                            <select name="moisture_level" id="moisture_level" class="syn-select">
                                <option value="">--</option>
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Observation
                        </button>
                        <a href="<?= base_url('bmg/batches/' . $batch['id']) ?>" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
