<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="dashboard employee-dashboard">
    <h1><?= esc($heading ?? 'Employee Portal') ?></h1>

    <?php if (empty($employee)): ?>
        <div class="alert alert-warning">
            Your employee record is not yet set up. Please contact the HR Department.
        </div>
    <?php else: ?>
        <div class="employee-info card">
            <h2><?= esc($employee['first_name'] ?? '') ?> <?= esc($employee['last_name'] ?? '') ?></h2>
            <table class="info-table">
                <tr><th>Employee Number</th><td><?= esc($employee['employee_number']) ?></td></tr>
                <tr><th>Department</th><td><?= esc($employee['department']) ?></td></tr>
                <tr><th>Position</th><td><?= esc($employee['position']) ?></td></tr>
                <tr><th>Date Hired</th><td><?= esc($employee['date_hired']) ?></td></tr>
            </table>
        </div>

        <div class="quick-actions">
            <a href="<?= base_url('profile') ?>" class="btn">My Profile</a>
            <a href="<?= base_url('clinic/consultations') ?>" class="btn">My Consultations</a>
            <a href="<?= base_url('counselling/appointments') ?>" class="btn">My Appointments</a>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
