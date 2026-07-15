<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <h1><?= esc($title) ?></h1>

    <?php if (!$valid): ?>
        <div class="alert alert-danger">
            <?= esc($error ?? 'Verification failed.') ?>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <strong>✓ Verified!</strong> This QR code is authentic.
        </div>

        <div class="card">
            <h2>Referral #<?= esc($referral['id']) ?></h2>
            <table class="info-table">
                <tr><th>From</th><td><?= esc($referral['from_module']) ?></td></tr>
                <tr><th>To</th><td><?= esc($referral['to_module']) ?></td></tr>
                <tr><th>Status</th><td><span class="status-badge status-<?= esc($referral['status']) ?>"><?= esc($referral['status']) ?></span></td></tr>
                <tr><th>Reason</th><td><?= esc($referral['reason']) ?></td></tr>
                <tr><th>Priority</th><td><?= esc($referral['priority'] ?? 'normal') ?></td></tr>
                <tr><th>Created</th><td><?= esc($referral['created_at']) ?></td></tr>
                <tr><th>Verified at</th><td><?= esc($referral['qr_verified_at']) ?></td></tr>
            </table>
        </div>
    <?php endif; ?>

    <a href="<?= base_url('dashboard') ?>" class="btn">Back to Dashboard</a>
</div>
<?= $this->endSection() ?>
