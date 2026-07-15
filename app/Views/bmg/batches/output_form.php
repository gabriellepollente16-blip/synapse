<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        ['label' => 'Batches',             'href' => base_url('bmg/batches')],
        ['label' => $batch['batch_code'],  'href' => base_url('bmg/batches/' . $batch['id'])],
        'Record Output',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'Record Output (Harvest)',
        'subtitle' => 'Capture the harvest weight and quality grade for batch ' . $batch['batch_code'],
    ]) ?>

    <?= view('components/flash') ?>

    <div class="section">
        <div class="info-card">
            <div class="info-card-title"><i class="fas fa-info-circle"></i> Constraints</div>
            <dl>
                <dt>Batch</dt>                    <dd><?= esc($batch['batch_code']) ?></dd>
                <dt>Input Total</dt>              <dd><?= number_format((float) $batch['input_weight_kg'], 2) ?> kg</dd>
                <dt>Maximum Output Allowed</dt>  <dd><?= number_format((float) $batch['input_weight_kg'], 2) ?> kg (cannot exceed input)</dd>
            </dl>
        </div>
    </div>

    <div class="section">
        <div class="card">
            <div class="card-body">
                <form action="<?= base_url('bmg/batches/' . $batch['id'] . '/output/store') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="output_weight_kg">Output Weight (kg) *</label>
                        <input type="number" name="output_weight_kg" id="output_weight_kg" required step="0.01" min="0.01"
                               max="<?= esc($batch['input_weight_kg']) ?>" placeholder="e.g., 8.50" value="<?= esc(old('output_weight_kg', $output['output_weight_kg'] ?? '')) ?>" class="syn-input">
                    </div>
                    <div class="form-group">
                        <label for="quality_grade">Quality Grade</label>
                        <select name="quality_grade" id="quality_grade" class="syn-select">
                            <option value="">--</option>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" rows="2" placeholder="e.g., Dark brown, earthy smell, no visible food scraps." class="syn-input"><?= esc(old('notes', $output['notes'] ?? '')) ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-trophy"></i> Record Output
                        </button>
                        <a href="<?= base_url('bmg/batches/' . $batch['id']) ?>" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
