<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="dashboard reports-dashboard">
    <h1><?= esc($heading ?? 'Reports Dashboard') ?></h1>

    <p class="lead">Read-only access to cross-module reports and analytics.</p>

    <div class="report-tiles">
        <a href="<?= base_url('bmg/reports') ?>" class="report-tile">
            <h3>BMG Reports</h3>
            <p>Yield by drum, duration by waste type, monthly totals</p>
        </a>
        <a href="<?= base_url('reports/clinic') ?>" class="report-tile">
            <h3>Clinic Reports</h3>
            <p>Consultation volume, wait times, diagnosis frequency</p>
        </a>
        <a href="<?= base_url('reports/counselling') ?>" class="report-tile">
            <h3>Counselling Reports</h3>
            <p>Appointment utilization, no-show rates</p>
        </a>
        <a href="<?= base_url('reports/inventory') ?>" class="report-tile">
            <h3>Inventory Reports</h3>
            <p>Stock levels, expiration tracking, reorder history</p>
        </a>
    </div>
</div>
<?= $this->endSection() ?>
