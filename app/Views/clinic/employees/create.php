<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>
<div class="container">
    <h1>New Employee</h1>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('clinic/employees/store') ?>" method="post" class="form-card">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="employee_number">Employee Number *</label>
            <input type="text" name="employee_number" id="employee_number" required maxlength="50"
                   value="<?= old('employee_number') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" name="first_name" id="first_name" required maxlength="100"
                       value="<?= old('first_name') ?>">
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" name="last_name" id="last_name" required maxlength="100"
                       value="<?= old('last_name') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" name="email" id="email" required maxlength="254"
                   value="<?= old('email') ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" name="department" id="department" maxlength="100"
                       value="<?= old('department') ?>">
            </div>
            <div class="form-group">
                <label for="position">Position</label>
                <input type="text" name="position" id="position" maxlength="100"
                       value="<?= old('position') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="date_hired">Date Hired</label>
            <input type="date" name="date_hired" id="date_hired"
                   value="<?= old('date_hired') ?>">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Employee</button>
            <a href="<?= base_url('clinic/employees') ?>" class="btn">Cancel</a>
        </div>

        <p class="help-text">A temporary password will be generated. The employee can change it after first login.</p>
    </form>
</div>
<?= $this->endSection() ?>
