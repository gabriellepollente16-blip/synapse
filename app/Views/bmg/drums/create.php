<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        ['label' => 'Drums',               'href' => base_url('bmg/drums')],
        'New Drum',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'Add New BMG Drum',
        'subtitle' => 'Register a new composting drum so it can host batches.',
    ]) ?>

    <?= view('components/flash') ?>

    <div class="section">
        <div class="card">
            <div class="card-body">
                <form action="<?= base_url('bmg/drums/store') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="drum_code">Drum Code *</label>
                            <input type="text" name="drum_code" id="drum_code" required maxlength="50" placeholder="BMG-001" value="<?= esc(old('drum_code')) ?>" class="syn-input">
                            <small>Use a unique code, e.g., BMG-001.</small>
                        </div>
                        <div class="form-group">
                            <label for="name">Name *</label>
                            <input type="text" name="name" id="name" required maxlength="150" placeholder="Drum #1 - Main Campus" value="<?= esc(old('name')) ?>" class="syn-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" maxlength="255" placeholder="Behind Cafeteria" value="<?= esc(old('location')) ?>" class="syn-input">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="capacity_kg">Capacity (kg) *</label>
                            <input type="number" name="capacity_kg" id="capacity_kg" required step="0.01" min="1" value="<?= esc(old('capacity_kg', 100)) ?>" class="syn-input">
                        </div>
                        <div class="form-group">
                            <label for="installation_date">Installation Date</label>
                            <input type="date" name="installation_date" id="installation_date" value="<?= esc(old('installation_date')) ?>" class="syn-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="syn-input"><?= esc(old('notes')) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Drum
                        </button>
                        <a href="<?= base_url('bmg/drums') ?>" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>