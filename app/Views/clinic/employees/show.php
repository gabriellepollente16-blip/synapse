<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <h1>Employee Details</h1>

    <div class="card">
        <h2><?= esc($user['first_name'] ?? '') ?> <?= esc($user['last_name'] ?? '') ?></h2>

        <table class="info-table">
            <tr><th>Employee Number</th><td><?= esc($employee['employee_number']) ?></td></tr>
            <tr><th>Email</th><td><?= esc($user['email'] ?? '-') ?></td></tr>
            <tr><th>Department</th><td><?= esc($employee['department'] ?? '-') ?></td></tr>
            <tr><th>Position</th><td><?= esc($employee['position'] ?? '-') ?></td></tr>
            <tr><th>Date Hired</th><td><?= esc($employee['date_hired'] ?? '-') ?></td></tr>
            <tr><th>Status</th><td><span class="status-badge status-<?= esc($employee['employment_status']) ?>"><?= esc($employee['employment_status']) ?></span></td></tr>
        </table>

        <div class="form-actions">
            <a href="<?= base_url('clinic/employees/edit/' . $employee['id']) ?>" class="btn btn-primary">Edit</a>
            <a href="<?= base_url('clinic/employees') ?>" class="btn">Back to List</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
