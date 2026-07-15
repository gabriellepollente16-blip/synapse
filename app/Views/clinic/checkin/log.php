<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <h1>Today's Check-Ins</h1>
    <p class="lead">Showing all RFID check-ins for today.</p>

    <table class="data-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Patient Type</th>
                <th>Module</th>
                <th>RFID Tag</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($checkins)): ?>
                <tr><td colspan="5" class="empty-state">No check-ins yet today.</td></tr>
            <?php else: ?>
                <?php foreach ($checkins as $c): ?>
                    <tr>
                        <td><?= esc(substr($c['checkin_at'], 11)) ?></td>
                        <td><?= esc($c['patient_type']) ?></td>
                        <td><?= esc($c['module']) ?></td>
                        <td><code><?= esc($c['rfid_tag_scanned']) ?></code></td>
                        <td><?= esc($c['notes'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
