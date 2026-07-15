<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        ['label' => 'Waste Categories',    'href' => base_url('bmg/categories')],
        ['label' => $category['name'],     'href' => base_url('bmg/categories/edit/' . $category['id'])],
        'Edit',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'Edit ' . $category['name'],
        'subtitle' => 'Update category name, description, expected yield, reference duration, or status.',
    ]) ?>

    <?= view('components/flash') ?>

    <div class="section">
        <div class="card">
            <div class="card-body">
                <form action="<?= base_url('bmg/categories/update/' . $category['id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Code</label>
                        <input type="text" value="<?= esc($category['code']) ?>" disabled>
                        <small>Code cannot be changed.</small>
                    </div>
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" name="name" id="name" required maxlength="100" value="<?= esc($category['name']) ?>" class="syn-input">
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="2" class="syn-input"><?= esc($category['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expected_yield_pct">Expected Yield %</label>
                            <input type="number" name="expected_yield_pct" id="expected_yield_pct" step="0.01" min="0" max="100"
                                   value="<?= esc($category['expected_yield_pct'] ?? '') ?>" class="syn-input">
                        </div>
                        <div class="form-group">
                            <label for="reference_duration_days">Reference Duration (days)</label>
                            <input type="number" name="reference_duration_days" id="reference_duration_days" min="1" max="365"
                                   value="<?= esc($category['reference_duration_days'] ?? 45) ?>" class="syn-input">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="is_active">Status</label>
                        <select name="is_active" id="is_active" class="syn-select">
                            <option value="1" <?= $category['is_active'] ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= ! $category['is_active'] ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="<?= base_url('bmg/categories') ?>" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
