<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?php
$role          = $primaryRole ?? session()->get('primary_role');
$userName      = session()->get('full_name') ?? session()->get('first_name') ?? 'User';
$moduleCount   = (int) ($clinicVisible ?? false)
               + (int) ($counsellingVisible ?? false)
               + (int) ($bmgVisible ?? false);
?>

<!-- ============================================================
     WELCOME BANNER
     ============================================================ -->
<div class="welcome-band">
    <div class="welcome-band-text">
        <h1>Welcome back<?= $userName ? ', ' . esc($userName) : '' ?></h1>
        <p>Here's a quick overview of your accessible modules &mdash; jump into any section from the cards below.</p>
    </div>
    <div class="welcome-band-meta">
        <div><i class="fas fa-calendar"></i> <?= date('l, M j, Y') ?></div>
        <div><i class="fas fa-layer-group"></i> <?= $moduleCount ?> module<?= $moduleCount === 1 ? '' : 's' ?> &middot; <?= esc(ucwords(str_replace('_', ' ', $role ?? 'guest'))) ?></div>
    </div>
</div>

<!-- ============================================================
     KPI SUMMARY CARDS
     ============================================================ -->
<div class="stats-grid">
    <a href="<?= base_url('dashboard') ?>" class="stat-card stat-link" aria-label="Accessible modules">
        <div class="stat-icon blue"><i class="fas fa-layer-group"></i></div>
        <div class="stat-info">
            <h3><?= esc(number_format($moduleCount)) ?></h3>
            <p>Accessible Modules</p>
        </div>
    </a>
    <a href="<?= base_url('dashboard') ?>" class="stat-card stat-link" aria-label="Pending tasks">
        <div class="stat-icon <?= ($pendingTasks ?? 0) > 0 ? 'red' : 'green' ?>"><i class="fas fa-bell"></i></div>
        <div class="stat-info">
            <h3><?= esc(number_format($pendingTasks ?? 0)) ?></h3>
            <p>Pending Tasks</p>
        </div>
    </a>
    <a href="#recent-activity" class="stat-card stat-link" aria-label="Recent activities">
        <div class="stat-icon teal"><i class="fas fa-clock-rotate-left"></i></div>
        <div class="stat-info">
            <h3><?= esc(number_format(count($recentActivity ?? []))) ?></h3>
            <p>Recent Activities</p>
        </div>
    </a>
    <a href="#notifications" class="stat-card stat-link" aria-label="Unread notifications">
        <div class="stat-icon <?= ($unreadNotifCount ?? 0) > 0 ? 'orange' : 'gray' ?>"><i class="fas fa-bell"></i></div>
        <div class="stat-info">
            <h3><?= esc(number_format($unreadNotifCount ?? 0)) ?></h3>
            <p>Notifications</p>
        </div>
    </a>
</div>

<!-- ============================================================
     MODULE OVERVIEW — compact academic cards
     ============================================================ -->
<div class="module-cards-grid">

    <?php if ($bmgVisible): ?>
        <div class="card module-card module-card-bmg">
            <div class="module-card-header">
                <div class="module-card-headline">
                    <i class="fas fa-drum module-card-icon"></i>
                    <div class="module-card-titles">
                        <span class="module-card-title">BMG Module</span>
                        <span class="module-card-subtitle">Composting &amp; Facility Ops</span>
                    </div>
                </div>
                <a href="<?= base_url('dashboard/bmg') ?>" class="module-card-link" aria-label="Open BMG module">
                    Open <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="module-card-body">
                <dl class="module-card-stats">
                    <div class="module-card-stat">
                        <dt>Drums</dt>
                        <dd><?= esc(number_format($bmgKpis['total_drums'])) ?></dd>
                    </div>
                    <div class="module-card-stat">
                        <dt>Active Batches</dt>
                        <dd><?= esc(number_format($bmgKpis['active_batches'])) ?></dd>
                    </div>
                    <div class="module-card-stat">
                        <dt>Alerts</dt>
                        <dd class="<?= $bmgKpis['idle_alerts'] > 0 ? 'kpi-warn' : '' ?>"><?= esc(number_format($bmgKpis['idle_alerts'])) ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($clinicVisible): ?>
        <div class="card module-card module-card-clinic">
            <div class="module-card-header">
                <div class="module-card-headline">
                    <i class="fas fa-stethoscope module-card-icon"></i>
                    <div class="module-card-titles">
                        <span class="module-card-title">Clinic Module</span>
                        <span class="module-card-subtitle">Triage &amp; Consultations</span>
                    </div>
                </div>
                <a href="<?= base_url('dashboard/clinic') ?>" class="module-card-link" aria-label="Open Clinic module">
                    Open <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="module-card-body">
                <dl class="module-card-stats">
                    <div class="module-card-stat">
                        <dt>Consultations (30d)</dt>
                        <dd><?= esc(number_format($clinicKpis['consultations_30d'])) ?></dd>
                    </div>
                    <div class="module-card-stat">
                        <dt>Triage Alerts</dt>
                        <dd class="<?= $clinicKpis['triage_high'] > 0 ? 'kpi-warn' : '' ?>"><?= esc(number_format($clinicKpis['triage_high'])) ?></dd>
                    </div>
                    <div class="module-card-stat">
                        <dt>Low Stock</dt>
                        <dd class="<?= $clinicKpis['low_stock'] > 0 ? 'kpi-warn' : '' ?>"><?= esc(number_format($clinicKpis['low_stock'])) ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($counsellingVisible): ?>
        <div class="card module-card module-card-counselling">
            <div class="module-card-header">
                <div class="module-card-headline">
                    <i class="fas fa-hand-holding-heart module-card-icon"></i>
                    <div class="module-card-titles">
                        <span class="module-card-title">Counselling</span>
                        <span class="module-card-subtitle">Appointments &amp; Crisis</span>
                    </div>
                </div>
                <a href="<?= base_url('dashboard/counsellor') ?>" class="module-card-link" aria-label="Open Counselling module">
                    Open <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="module-card-body">
                <dl class="module-card-stats">
                    <div class="module-card-stat">
                        <dt>Sessions (30d)</dt>
                        <dd><?= esc(number_format($counsellingKpis['appointments_30d'])) ?></dd>
                    </div>
                    <div class="module-card-stat">
                        <dt>Crisis Alerts</dt>
                        <dd class="<?= $counsellingKpis['crisis_alerts'] > 0 ? 'kpi-warn' : '' ?>"><?= esc(number_format($counsellingKpis['crisis_alerts'])) ?></dd>
                    </div>
                    <div class="module-card-stat">
                        <dt>No-Shows</dt>
                        <dd><?= esc(number_format($counsellingKpis['no_shows'])) ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- ============================================================
     TWO-COLUMN ROW: Recent Activity | Notifications
     ============================================================ -->
<div class="dashboard-grid">

    <div class="card" id="recent-activity">
        <div class="card-header">
            <i class="fas fa-clock-rotate-left"></i> Recent Activity
        </div>
        <div class="card-body">
            <?php if (empty($recentActivity)): ?>
                <div class="placeholder-box">
                    <i class="fas fa-circle-info placeholder-icon"></i>
                    <p class="placeholder-text">No recent activity recorded.</p>
                </div>
            <?php else: ?>
                <ul class="activity-feed">
                    <?php foreach ($recentActivity as $log): ?>
                        <li class="activity-feed-item">
                            <div class="activity-feed-dot"></div>
                            <div class="activity-feed-body">
                                <p class="activity-feed-text">
                                    <strong><?= esc($log['action'] ?? 'event') ?></strong>
                                    <?php if (! empty($log['entity_type'])): ?>
                                        <span class="muted-text">on <?= esc($log['entity_type']) ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="activity-feed-time">
                                    <?= esc(date('M j, g:i A', strtotime($log['created_at'] ?? 'now'))) ?>
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" id="notifications">
        <div class="card-header">
            <i class="fas fa-bell"></i> Notifications
            <?php if (($unreadNotifCount ?? 0) > 0): ?>
                <span class="badge badge-warning" style="margin-left: auto;"><?= esc($unreadNotifCount) ?> unread</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <div class="placeholder-box">
                    <i class="fas fa-circle-check placeholder-icon" style="color: var(--success);"></i>
                    <p class="placeholder-text">No notifications &mdash; you're all caught up.</p>
                </div>
            <?php else: ?>
                <ul class="activity-feed">
                    <?php foreach ($notifications as $n): ?>
                        <li class="activity-feed-item <?= empty($n['is_read']) ? 'notification-unread' : '' ?>">
                            <div class="activity-feed-dot"></div>
                            <div class="activity-feed-body">
                                <p class="activity-feed-text">
                                    <strong><?= esc($n['title'] ?? 'Notification') ?></strong>
                                    <?php if (! empty($n['message'])): ?>
                                        <span class="muted-text">&mdash; <?= esc($n['message']) ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="activity-feed-time">
                                    <?= esc(date('M j, g:i A', strtotime($n['created_at'] ?? 'now'))) ?>
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     QUICK ACTIONS
     ============================================================ -->
<div class="card" id="quick-actions">
    <div class="card-header">
        <i class="fas fa-bolt"></i> Quick Actions
    </div>
    <div class="card-body">
        <div class="quick-actions-grid">
            <?php if ($bmgVisible): ?>
                <a href="<?= base_url('dashboard/bmg') ?>" class="quick-action-tile">
                    <i class="fas fa-drum"></i> Open BMG Dashboard
                </a>
                <a href="<?= base_url('bmg/drums') ?>" class="quick-action-tile">
                    <i class="fas fa-drum"></i> Manage Drums
                </a>
                <a href="<?= base_url('bmg/reports') ?>" class="quick-action-tile">
                    <i class="fas fa-chart-line"></i> Reports
                </a>
                <a href="<?= base_url('bmg/categories') ?>" class="quick-action-tile">
                    <i class="fas fa-tags"></i> Waste Categories
                </a>
            <?php endif; ?>
            <?php if ($clinicVisible): ?>
                <a href="<?= base_url('clinic/consultations') ?>" class="quick-action-tile">
                    <i class="fas fa-stethoscope"></i> Clinic Consultations
                </a>
                <a href="<?= base_url('clinic/triage') ?>" class="quick-action-tile">
                    <i class="fas fa-triangle-exclamation"></i> Triage
                </a>
            <?php endif; ?>
            <?php if ($counsellingVisible): ?>
                <a href="<?= base_url('counselling') ?>" class="quick-action-tile">
                    <i class="fas fa-calendar-check"></i> Counselling
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* ============================================================
       Welcome band — restrained, academic
       ============================================================ */
    .welcome-band {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        flex-wrap: wrap;
        background: var(--primary-700, #1d4ed8);
        color: white;
        padding: 1rem 1.25rem;
        border-radius: 0.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    }
    .welcome-band-text h1 {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.15rem;
        color: white;
        letter-spacing: -0.01em;
    }
    .welcome-band-text p {
        margin: 0;
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 400;
    }
    .welcome-band-meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.15rem;
        font-size: 0.78rem;
        color: rgba(255, 255, 255, 0.9);
    }
    .welcome-band-meta i { margin-right: 0.35rem; opacity: 0.85; }

    /* ============================================================
       Stat card link (KPI tiles)
       ============================================================ */
    .stat-link {
        text-decoration: none;
        color: inherit;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }
    .stat-link:hover {
        box-shadow: var(--shadow-sm, 0 1px 3px rgba(0, 0, 0, 0.06));
    }

    /* ============================================================
       Module cards grid — balanced, academic
       ============================================================ */
    .module-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .module-card {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid var(--gray-200, #e2e8f0);
    }

    /* Compact header — single row, icon + titles + small link */
    .module-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.55rem 0.9rem;
        color: #fff;
        min-height: 44px;
    }
    .module-card-bmg          .module-card-header { background: #16a34a; }
    .module-card-clinic       .module-card-header { background: #1e40af; }
    .module-card-counselling  .module-card-header { background: #0f766e; }

    .module-card-headline {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        min-width: 0;
        flex: 1;
    }
    .module-card-icon {
        font-size: 0.95rem;
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .module-card-titles {
        display: flex;
        flex-direction: column;
        line-height: 1.15;
        min-width: 0;
    }
    .module-card-title {
        font-size: 0.92rem;
        font-weight: 600;
        letter-spacing: -0.005em;
    }
    .module-card-subtitle {
        font-size: 0.72rem;
        opacity: 0.85;
        margin-top: 1px;
    }

    /* Small inline link in the header — replaces the full-width button */
    .module-card-link {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.25rem 0.55rem;
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        border-radius: 0.3rem;
        font-size: 0.75rem;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
        transition: background 0.15s ease;
        flex-shrink: 0;
    }
    .module-card-link:hover {
        background: rgba(255, 255, 255, 0.28);
        color: #fff;
        text-decoration: none;
    }
    .module-card-link i { font-size: 0.7rem; }

    /* Compact statistics table */
    .module-card-body { padding: 0.6rem 0.9rem 0.75rem; }
    .module-card-stats {
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
    }
    .module-card-stat {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.32rem 0;
        border-bottom: 1px dashed var(--gray-100, #f1f5f9);
        font-size: 0.82rem;
    }
    .module-card-stat:last-child { border-bottom: 0; }
    .module-card-stat dt {
        margin: 0;
        color: var(--gray-600, #475569);
        font-weight: 400;
    }
    .module-card-stat dd {
        margin: 0;
        color: var(--gray-900, #0f172a);
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }
    .kpi-warn { color: #b91c1c !important; }

    /* ============================================================
       Two-column dashboard grid (Recent Activity + Notifications)
       Equal-height cards
       ============================================================ */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.25rem;
    }
    .dashboard-grid > .card {
        display: flex;
        flex-direction: column;
    }
    .dashboard-grid > .card > .card-body {
        flex: 1;
    }

    /* ============================================================
       Activity feed
       ============================================================ */
    .activity-feed {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }
    .activity-feed-item {
        display: flex;
        gap: 0.65rem;
        align-items: flex-start;
    }
    .activity-feed-item.notification-unread .activity-feed-text {
        font-weight: 600;
        color: var(--gray-900, #0f172a);
    }
    .activity-feed-item.notification-unread .activity-feed-dot {
        background: var(--primary-600, #9d2235);
        box-shadow: 0 0 0 3px rgba(157, 34, 53, 0.15);
    }
    .activity-feed-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--primary-500, #b8304a);
        margin-top: 0.45rem;
        flex-shrink: 0;
    }
    .activity-feed-body { flex: 1; min-width: 0; }
    .activity-feed-text { margin: 0; font-size: 0.83rem; color: var(--gray-700, #334155); }
    .activity-feed-time { margin: 0; font-size: 0.7rem; color: var(--gray-500, #64748b); }
    .muted-text { color: var(--gray-500, #64748b); font-size: 0.76rem; }

    /* ============================================================
       Quick Actions
       ============================================================ */
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.4rem;
    }
    .quick-action-tile {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: var(--gray-50, #f8fafc);
        border: 1px solid var(--gray-200, #e2e8f0);
        border-radius: 0.375rem;
        text-decoration: none;
        color: var(--gray-800, #1f2937);
        font-size: 0.82rem;
        font-weight: 500;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .quick-action-tile:hover {
        background: #fff;
        border-color: var(--primary-200, #f5bcc6);
        color: var(--primary-700, #7b1f2c);
        text-decoration: none;
    }
    .quick-action-tile i {
        color: var(--primary-600, #9D2235);
        font-size: 0.85rem;
    }

    /* ============================================================
       Placeholder
       ============================================================ */
    .placeholder-box {
        text-align: center;
        padding: 1rem 0.5rem;
    }
    .placeholder-icon {
        font-size: 1.25rem;
        color: var(--gray-400, #9ca3af);
        margin-bottom: 0.4rem;
    }
    .placeholder-text {
        color: var(--gray-500, #64748b);
        font-size: 0.85rem;
        margin: 0;
    }

    /* ============================================================
       Responsive
       ============================================================ */
    @media (max-width: 1024px) {
        .dashboard-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .welcome-band { flex-direction: column; align-items: flex-start; }
        .welcome-band-meta { align-items: flex-start; }
    }
</style>

<?= $this->endSection() ?>