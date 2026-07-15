<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        ['label' => 'Batches',             'href' => base_url('bmg/batches')],
        ['label' => $batch['batch_code'],  'href' => base_url('bmg/batches/' . $batch['id'])],
        'Record Input',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'Record Input',
        'subtitle' => 'Add an additional input to batch ' . $batch['batch_code'],
    ]) ?>

    <?= view('components/flash') ?>

    <div class="section">
        <div class="info-card">
            <div class="info-card-title"><i class="fas fa-info-circle"></i> Current State</div>
            <dl>
                <dt>Batch</dt>           <dd><?= esc($batch['batch_code']) ?></dd>
                <dt>Current Total</dt>   <dd><?= number_format((float) $batch['input_weight_kg'], 2) ?> kg</dd>
                <dt>Drum Capacity</dt>   <dd><?= number_format((float) ($batch['drum_capacity_kg'] ?? 0), 1) ?> kg</dd>
            </dl>
        </div>
    </div>

    <div class="section">
        <div class="card">
            <div class="card-body">
                <form action="<?= base_url('bmg/batches/' . $batch['id'] . '/inputs/store') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="weight_kg">Weight (kg) *</label>
                        <input type="number" name="weight_kg" id="weight_kg" required step="0.01" min="0.01" placeholder="e.g., 10.50" value="<?= esc(old('weight_kg', $input['weight_kg'] ?? '')) ?>" class="syn-input">
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" rows="2" class="syn-input"><?= esc(old('notes', $input['notes'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Record Input
                        </button>
                        <a href="<?= base_url('bmg/batches/' . $batch['id']) ?>" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
