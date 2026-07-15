<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        ['label' => 'Waste Categories',    'href' => base_url('bmg/categories')],
        'New Category',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'Add Waste Category',
        'subtitle' => 'Define a new waste type for batch tagging.',
    ]) ?>

    <?= view('components/flash') ?>

    <div class="section">
        <div class="card">
            <div class="card-body">
                <form action="<?= base_url('bmg/categories/store') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="code">Code *</label>
                            <input type="text" name="code" id="code" required maxlength="50" placeholder="food_waste" value="<?= esc(old('code')) ?>" class="syn-input">
                            <small>Lowercase, underscores (e.g., food_waste, twigs_leaves, mixed)</small>
                        </div>
                        <div class="form-group">
                            <label for="name">Name *</label>
                            <input type="text" name="name" id="name" required maxlength="100" placeholder="Food Scraps" value="<?= esc(old('name')) ?>" class="syn-input">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="2" class="syn-input"><?= esc(old('description')) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="expected_yield_pct">Expected Yield % (reference)</label>
                        <input type="number" name="expected_yield_pct" id="expected_yield_pct" step="0.01" min="0" max="100"
                               value="<?= esc(old('expected_yield_pct')) ?>" placeholder="e.g., 35.00" class="syn-input">
                    </div>
                    <div class="form-group">
                        <label for="reference_duration_days">Reference Duration (days)</label>
                        <input type="number" name="reference_duration_days" id="reference_duration_days" min="1" max="365"
                               value="<?= esc(old('reference_duration_days', 45)) ?>" placeholder="e.g., 30" class="syn-input">
                        <small>How many days a typical batch of this waste type takes to fully decompose. Used to estimate the completion date for new batches.</small>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Category
                        </button>
                        <a href="<?= base_url('bmg/categories') ?>" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
