<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        'Waste Categories',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'Waste Categories',
        'subtitle' => 'Tag batches by waste type to enable comparative analytics.',
        'actions'  =>
            '<a href="' . base_url('bmg/categories/create') . '" class="btn btn-primary">'
            . '<i class="fas fa-plus"></i> New Category</a>',
    ]) ?>

    <?= view('components/flash') ?>

    <div class="section">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Expected Yield %</th>
                    <th>Active</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="6" class="empty-state">No categories yet. <a href="<?= base_url('bmg/categories/create') ?>">Add the first one</a>.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><code><?= esc($c['code']) ?></code></td>
                            <td><?= esc($c['name']) ?></td>
                            <td><?= esc($c['description'] ?? '—') ?></td>
                            <td><?= $c['expected_yield_pct'] !== null ? number_format((float) $c['expected_yield_pct'], 1) . '%' : '—' ?></td>
                            <td><?= $c['is_active'] ? '<span class="status-badge status-completed">Active</span>' : '<span class="status-badge status-cancelled">Inactive</span>' ?></td>
                            <td class="text-right">
                                <a href="<?= base_url('bmg/categories/edit/' . $c['id']) ?>" class="btn btn-sm">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?= $this->endSection() ?>
