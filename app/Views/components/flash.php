<?php
/**
 * Flash message partial.
 *
 * Renders success / error / info / warning flash messages. Pulls from
 * CodeIgniter's standard `session()->getFlashdata()` keys. If a
 * `getFlashdata('errors')` array is present, renders each entry as a
 * bulleted list inside an alert-danger block.
 *
 * Usage: `<?= view('components/flash') ?>`
 */
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
$info    = session()->getFlashdata('info');
$warning = session()->getFlashdata('warning');
$errors  = session()->getFlashdata('errors');
?>
<?php if ($success): ?>
    <div class="alert alert-success" role="status">
        <i class="fas fa-check-circle"></i>
        <?= esc($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <?= esc($error) ?>
    </div>
<?php endif; ?>

<?php if ($info): ?>
    <div class="alert" role="status" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;">
        <i class="fas fa-info-circle"></i>
        <?= esc($info) ?>
    </div>
<?php endif; ?>

<?php if ($warning): ?>
    <div class="alert" role="status" style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a;">
        <i class="fas fa-exclamation-triangle"></i>
        <?= esc($warning) ?>
    </div>
<?php endif; ?>

<?php if (! empty($errors) && is_array($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <strong>Please fix the following:</strong>
        <ul style="margin: 0.5rem 0 0 1.25rem;">
            <?php foreach ($errors as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php
