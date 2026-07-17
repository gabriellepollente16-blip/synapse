<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
    /* Permission gate — mirrors the controller's check so the "Create Referral"
       CTA only renders for employees who actually hold the `referrals.create`
       permission. The role permission is granted in
       app/Database/Seeds/RolePermissionSeeder.php (employee → referrals.create).
       We resolve it via the session-cached permissions list to avoid an extra
       DB round-trip on every dashboard load. */
    $sessionPerms = session()->get('permissions') ?? [];
    $canCreateReferral = in_array('referrals.create', $sessionPerms, true);
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-paper-plane"></i></div>
        <div class="stat-info">
            <h3><?= esc($stats['referrals_submitted'] ?? 0) ?></h3>
            <p>Referrals Submitted</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info">
            <h3><?= esc($stats['referrals_pending'] ?? 0) ?></h3>
            <p>Pending Referrals</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-file-medical"></i></div>
        <div class="stat-info">
            <h3><?= esc($stats['consultations'] ?? 0) ?></h3>
            <p>Clinic Consultations</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-bell"></i></div>
        <div class="stat-info">
            <h3><?= esc($unreadNotifCount ?? 0) ?></h3>
            <p>Unread Notifications</p>
        </div>
    </div>
</div>

<div class="section-grid">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-id-badge" style="color: var(--primary-500); margin-right: 0.5rem;"></i>
            Employee Profile
        </div>
        <div class="card-body">
            <?php if ($employee): ?>
                <div class="profile-card id-card">
                    <div class="id-card-decor"></div>
                    <div class="id-card-body">
                        <div class="profile-code">
                            <?php
                                // Defensive escaping for QR attribute. The QR image
                                // is rendered through api.qrserver.com which reads
                                // the data via URL — escape AND urlencode the value
                                // so attribute injection or URL injection cannot
                                // break out of the src="..." quoting.
                                $qrPayload = $employee['qr_code'] ?: ($employee['employee_number'] ?? '');
                                $qrAttr    = esc(urlencode($qrPayload), 'attr');
                            ?>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?= $qrAttr ?>" alt="Employee QR Code">
                        </div>
                        <div class="profile-details">
                            <p class="profile-label">Employee Identity Card</p>
                            <h2 class="profile-name"><?= esc(session()->get('full_name')) ?></h2>
                            <p class="profile-secondary"><?= esc($employee['employee_number']) ?></p>
                            <div class="profile-meta">
                                <div>
                                    <p class="profile-meta-label">Department</p>
                                    <p class="profile-meta-value"><?= esc($employee['department'] ?: 'N/A') ?></p>
                                </div>
                                <div>
                                    <p class="profile-meta-label">Position</p>
                                    <p class="profile-meta-value"><?= esc($employee['position'] ?: 'N/A') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="id-card-footer">
                        <span>RFID Tag: <?= esc($employee['rfid_tag'] ?: 'Unassigned') ?></span>
                        <span>
                            <i class="fas fa-signal"></i>
                            <?= esc(ucwords(str_replace('_', ' ', (string) ($employee['employment_status'] ?? 'active')))) ?>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div class="placeholder-box">
                    <i class="far fa-id-card placeholder-icon"></i>
                    <p class="placeholder-text">
                        Your employee record is not yet set up. Please contact the HR Department.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" id="quick-actions">
        <div class="card-header">
            <i class="fas fa-bolt"></i> Quick Actions
        </div>
        <div class="card-body">
            <div class="quick-actions-box">
                <p class="quick-actions-label">Daily actions</p>
                <div class="quick-action-pills">
                    <?php if ($canCreateReferral): ?>
                        <a href="<?= base_url('clinic/referrals/create') ?>" class="pill pill-orange">
                            <i class="fas fa-paper-plane"></i> Create Referral
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('profile') ?>" class="pill pill-teal">
                        <i class="fas fa-user-gear"></i> My Profile
                    </a>
                    <a href="<?= base_url('clinic/consultations') ?>" class="pill pill-green">
                        <i class="fas fa-file-medical"></i> My Consultations
                    </a>
                    <a href="<?= base_url('counselling/appointments') ?>" class="pill pill-blue">
                        <i class="fas fa-calendar-check"></i> My Appointments
                    </a>
                    <a href="<?= base_url('iot/kiosk') ?>" target="_blank" rel="noopener" class="pill pill-purple">
                        <i class="fas fa-qrcode"></i> Check-in Kiosk
                    </a>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 1.25rem 0;">

            <p class="quick-actions-label">Recent Notifications</p>
            <?php if (empty($notifications)): ?>
                <p class="muted-text" style="margin: 0.5rem 0 0;">You're all caught up.</p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 0.5rem 0 0; display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($notifications as $n): ?>
                        <li style="padding: 0.5rem 0.75rem; border: 1px solid var(--gray-200); border-radius: var(--radius-md); background: <?= empty($n['is_read']) ? 'var(--primary-50)' : '#fff' ?>;">
                            <strong style="font-size: 0.85rem; color: var(--gray-900); display: block;"><?= esc($n['title']) ?></strong>
                            <span style="font-size: 0.75rem; color: var(--gray-500);"><?= esc($n['message'] ?? '') ?></span>
                            <span style="font-size: 0.7rem; color: var(--gray-400); display: block; margin-top: 0.25rem;">
                                <?= esc($n['created_at'] ?? '') ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" id="my-referrals">
    <div class="card-header">
        <i class="fas fa-arrow-right-arrow-left" style="color: #F59E0B; margin-right: 0.5rem;"></i>
        My Submitted Referrals
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($myReferrals)): ?>
            <div class="placeholder-box" style="margin: 0;">
                <i class="far fa-paper-plane placeholder-icon"></i>
                <p class="placeholder-text">No referrals submitted yet.</p>
                <?php if ($canCreateReferral): ?>
                    <a href="<?= base_url('clinic/referrals/create') ?>" class="btn btn-primary" style="margin-top: 0.75rem;">
                        <i class="fas fa-plus"></i> Create Your First Referral
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Student</th>
                            <th scope="col">Direction</th>
                            <th scope="col">Reason</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myReferrals as $r): ?>
                            <tr>
                                <td style="white-space: nowrap;"><?= date('M d, Y h:i A', strtotime($r['created_at'])) ?></td>
                                <td style="font-weight: 600;">
                                    <?= esc(($r['student_first'] ?? '') . ' ' . ($r['student_last'] ?? '')) ?>
                                    <?php if (! empty($r['student_number'])): ?>
                                        <div style="font-size: 0.72rem; color: var(--gray-500); font-weight: 400;">
                                            <?= esc($r['student_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.75rem;">
                                    <?= esc(ucfirst(str_replace('_', ' → ', $r['direction']))) ?>
                                </td>
                                <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= esc($r['reason']) ?>
                                </td>
                                <td>
                                    <?php
                                        // Same color logic as clinic/referrals/index.php —
                                        // keeps the employee view visually identical to the
                                        // existing referrals queue.
                                        $priorityStyle = match ($r['priority']) {
                                            'emergency' => 'background: #FEF2F2; color: #DC2626;',
                                            'urgent'    => 'background: #FFF7ED; color: #EA580C;',
                                            default     => 'background: #ECFDF5; color: #059669;',
                                        };
                                    ?>
                                    <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?= $priorityStyle ?>">
                                        <?= esc(ucfirst($r['priority'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                        $statusStyle = match ($r['status']) {
                                            'accepted'    => 'background: #ECFDF5; color: #059669;',
                                            'declined'    => 'background: #FEF2F2; color: #DC2626;',
                                            'completed'   => 'background: var(--primary-50); color: var(--primary-700);',
                                            'in_progress' => 'background: #EFF6FF; color: #1D4ED8;',
                                            default       => 'background: #FFFBEB; color: #D97706;',
                                        };
                                    ?>
                                    <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?= $statusStyle ?>">
                                        <?= esc(ucfirst(str_replace('_', ' ', $r['status']))) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card" id="my-consultations">
    <div class="card-header">
        <i class="fas fa-file-medical" style="color: var(--primary-500); margin-right: 0.5rem;"></i>
        My Recent Consultations
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($myConsultations)): ?>
            <div class="placeholder-box" style="margin: 0;">
                <i class="far fa-file-medical placeholder-icon"></i>
                <p class="placeholder-text">
                    No clinic consultations on record. Self-admit at the clinic kiosk
                    to start a visit — your record will appear here automatically.
                </p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Chief Complaint</th>
                            <th scope="col">Diagnosis</th>
                            <th scope="col">Attending Staff</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myConsultations as $c): ?>
                            <tr>
                                <td style="white-space: nowrap;"><?= date('M d, Y', strtotime($c['consultation_date'])) ?></td>
                                <td><?= esc($c['chief_complaint']) ?></td>
                                <td><?= esc($c['diagnosis'] ?? '—') ?></td>
                                <td>
                                    <?= esc(($c['staff_first'] ?? '') . ' ' . ($c['staff_last'] ?? '')) ?>
                                </td>
                                <td>
                                    <?php
                                        $cStatus = $c['status'];
                                        $cBadgeClass = match ($cStatus) {
                                            'completed'   => 'badge-success',
                                            'in_session'  => 'badge-info',
                                            'called'      => 'badge-warning',
                                            'in_progress' => 'badge-info',
                                            'follow_up'   => 'badge-warning',
                                            default       => 'badge-info',
                                        };
                                        $cIcon = match ($cStatus) {
                                            'completed'  => 'fa-check',
                                            'in_session' => 'fa-stethoscope',
                                            'called'     => 'fa-bullhorn',
                                            default      => 'fa-clock',
                                        };
                                    ?>
                                    <span class="badge <?= $cBadgeClass ?>">
                                        <i class="fas <?= $cIcon ?>"></i>
                                        <?= esc(ucfirst(str_replace('_', ' ', $cStatus))) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .section-grid {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 1.25rem;
        margin-top: 1.25rem;
    }
    .profile-card {
        position: relative;
        overflow: hidden;
        min-height: 260px;
        border-radius: 1rem;
        background: linear-gradient(135deg, #0f766e, #0f4f59);
        color: white;
        box-shadow: var(--shadow-md);
    }
    .id-card-decor {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 42%),
                    radial-gradient(circle at bottom left, rgba(255,255,255,0.12), transparent 30%);
        pointer-events: none;
    }
    .id-card-body {
        position: relative;
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 1rem;
        align-items: center;
        padding: 1.5rem;
        z-index: 1;
    }
    .profile-code img {
        width: 140px;
        height: 140px;
        display: block;
        border-radius: 1rem;
        background: white;
        padding: 0.5rem;
    }
    .profile-details { color: white; }
    .profile-label {
        margin: 0 0 0.5rem;
        font-size: 0.75rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.75);
    }
    .id-card-footer {
        position: relative;
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem;
        margin: 0 1.5rem 1.25rem;
        border-radius: 0.9rem;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        color: rgba(255,255,255,0.9);
        font-size: 0.9rem;
        z-index: 1;
    }
    .profile-name {
        margin: 0;
        font-size: 1.5rem;
        color: var(--gray-900);
    }
    .profile-secondary {
        margin: 0.5rem 0 0;
        color: var(--primary-700);
        font-weight: 600;
    }
    .profile-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-top: 1rem;
    }
    .profile-meta-label {
        margin: 0 0 0.25rem;
        font-size: 0.75rem;
        color: var(--gray-400);
    }
    .profile-meta-value {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--gray-700);
    }
    .placeholder-box {
        margin-top: 1rem;
        padding: 2rem;
        border-radius: 0.75rem;
        background: var(--gray-50);
        border: 1px dashed var(--gray-200);
        text-align: center;
    }
    .placeholder-icon {
        font-size: 2.25rem;
        color: var(--gray-300);
        margin-bottom: 0.75rem;
    }
    .placeholder-text {
        margin: 0;
        color: var(--gray-500);
        font-size: 0.95rem;
    }
    .quick-actions-box { margin-top: 0.5rem; }
    .quick-actions-label {
        margin: 0 0 0.5rem;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--gray-500);
        font-weight: 600;
    }
    .quick-action-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.75rem;
        border-radius: var(--radius-pill);
        font-size: 0.78rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        transition: background var(--transition-fast), color var(--transition-fast);
    }
    a.pill:hover { text-decoration: none; transform: translateY(-1px); }
    .pill-orange { background: #FFF7ED; color: #C2410C; border-color: #FED7AA; }
    .pill-orange:hover { background: #FFEDD5; color: #9A3412; }
    .pill-teal   { background: #F0FDFA; color: #0F766E; border-color: #99F6E4; }
    .pill-teal:hover   { background: #CCFBF1; color: #115E59; }
    .pill-green  { background: #ECFDF5; color: #047857; border-color: #A7F3D0; }
    .pill-green:hover  { background: #D1FAE5; color: #065F46; }
    .pill-blue   { background: #EFF6FF; color: #1D4ED8; border-color: #BFDBFE; }
    .pill-blue:hover   { background: #DBEAFE; color: #1E40AF; }
    .pill-purple { background: #F5F3FF; color: #5B21B6; border-color: #DDD6FE; }
    .pill-purple:hover { background: #EDE9FE; color: #4C1D95; }

    @media (max-width: 900px) {
        .section-grid { grid-template-columns: 1fr; }
    }
</style>
<?= $this->endSection() ?>
