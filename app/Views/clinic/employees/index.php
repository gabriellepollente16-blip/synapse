<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <h1>Employees</h1>

    <div class="actions">
        <a href="<?= base_url('clinic/employees/create') ?>" class="btn btn-primary">+ New Employee</a>
        <a href="<?= base_url('clinic/checkin/scan') ?>" class="btn">RFID Check-In</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>

    <table class="data-table">
        <thead>
            <tr>
                <th>Employee #</th>
                <th>Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($employees)): ?>
                <tr><td colspan="6" class="empty-state">No employees yet. <a href="<?= base_url('clinic/employees/create') ?>">Create the first one</a>.</td></tr>
            <?php else: ?>
                <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><?= esc($emp['employee_number']) ?></td>
                        <td><?= esc($emp['user']['first_name'] ?? '') ?> <?= esc($emp['user']['last_name'] ?? '') ?></td>
                        <td><?= esc($emp['department'] ?? '-') ?></td>
                        <td><?= esc($emp['position'] ?? '-') ?></td>
                        <td><span class="status-badge status-<?= esc($emp['employment_status']) ?>"><?= esc($emp['employment_status']) ?></span></td>
                        <td>
                            <a href="<?= base_url('clinic/employees/' . $emp['id']) ?>" class="btn btn-sm">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
