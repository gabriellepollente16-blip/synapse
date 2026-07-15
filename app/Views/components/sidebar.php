<?php
/**
 * Sidebar navigation partial.
 *
 * Built dynamically from a priority-ordered list of sections so the
 * navigation order can be customised per role without rewriting the
 * markup. The Administration section is automatically placed directly
 * below the Main section for administrators (priority bumped from
 * the bottom of the list to 20), and is hidden entirely for every
 * other role. Section reordering is a matter of changing the
 * `priority` value on the relevant section definition.
 *
 * @var array $navSections   Built below; not passed in.
 * @var int   $adminPriority Computed below; not passed in.
 */

$roles          = session()->get('roles') ?? [];
$isAdmin        = in_array('admin', $roles);
$isClinic       = in_array('clinic_staff',   $roles) || $isAdmin;
$isCounsellor   = in_array('counsellor',     $roles) || $isAdmin;
$isStudent      = in_array('student',        $roles);
$isFacilities   = in_array('facilities_staff', $roles) || $isAdmin;
$isReportViewer = in_array('report_viewer',  $roles) || $isAdmin;

// Administration is hidden for non-admins, but we still keep the
// section definition in the array (instead of inlining conditionally)
// so the order is fully data-driven. Setting its priority to 999
// pushes it to the bottom should it ever leak through.
$adminPriority = $isAdmin ? 20 : 999;

$navSections = [
    // ---------- 10 ----------
    [
        'priority' => 10,
        'title'    => 'Main',
        'gate'     => true,
        'links'    => [
            [
                'icon'     => 'fa-th-large',
                'label'    => 'Dashboard',
                'href'     => 'dashboard',
                'isActive' => fn () => in_array(uri_string(), [
                    'dashboard', 'dashboard/bmg', 'dashboard/clinic',
                    'dashboard/counsellor', 'dashboard/admin', 'dashboard/reports',
                    'dashboard/employee', 'dashboard/student',
                ], true),
            ],
        ],
    ],

    // ---------- 20 (admin) / 999 (others, hidden) ----------
    [
        'priority' => $adminPriority,
        'title'    => 'Administration',
        'gate'     => $isAdmin,
        'links'    => [
            [
                'icon'     => 'fa-gauge-high',
                'label'    => 'Admin Console',
                'href'     => 'admin',
                'isActive' => fn () => uri_string() === 'admin',
            ],
            [
                'icon'     => 'fa-users-cog',
                'label'    => 'User Management',
                'href'     => 'admin/users',
                'isActive' => fn () => str_starts_with(uri_string(), 'admin/users'),
            ],
            [
                'icon'     => 'fa-user-shield',
                'label'    => 'Roles & Permissions',
                'href'     => 'admin/roles',
                'isActive' => fn () => str_starts_with(uri_string(), 'admin/roles'),
            ],
            [
                'icon'     => 'fa-shield-halved',
                'label'    => 'Audit Logs',
                'href'     => 'admin/audit',
                'isActive' => fn () => str_starts_with(uri_string(), 'admin/audit'),
            ],
            [
                'icon'     => 'fa-chart-bar',
                'label'    => 'Reports & Analytics',
                'href'     => 'reports',
                'isActive' => fn () => str_starts_with(uri_string(), 'reports'),
            ],
            [
                'icon'      => 'fa-puzzle-piece',
                'label'     => 'UI Components',
                'href'      => 'ui',
                'isActive'  => fn () => uri_string() === 'ui',
                'target'    => '_blank',
                'dataLabel' => 'UI Components',
            ],
        ],
    ],

    // ---------- 30 ----------
    [
        'priority' => 30,
        'title'    => 'Clinic',
        'gate'     => $isClinic,
        'links'    => [
            [
                'icon'     => 'fa-clipboard-list',
                'label'    => 'Consultations',
                'href'     => 'clinic/consultations',
                'isActive' => fn () => str_starts_with(uri_string(), 'clinic/consultation'),
            ],
            [
                'icon'     => 'fa-users',
                'label'    => 'Students',
                'href'     => 'clinic/students',
                'isActive' => fn () => str_starts_with(uri_string(), 'clinic/student'),
            ],
            [
                'icon'     => 'fa-arrow-right-arrow-left',
                'label'    => 'Referrals',
                'href'     => 'clinic/referrals',
                'isActive' => fn () => str_starts_with(uri_string(), 'clinic/referral'),
            ],
            [
                'icon'     => 'fa-desktop',
                'label'    => 'Check-In Kiosk',
                'href'     => 'iot/kiosk',
                'isActive' => fn () => false,
                'target'   => '_blank',
            ],
        ],
    ],

    // ---------- 40 ----------
    [
        'priority' => 40,
        'title'    => 'Inventory',
        'gate'     => $isClinic,
        'links'    => [
            [
                'icon'     => 'fa-pills',
                'label'    => 'Medicine Catalog',
                'href'     => 'inventory',
                'isActive' => fn () => uri_string() === 'inventory'
                                  || str_starts_with(uri_string(), 'inventory/medicines'),
            ],
            [
                'icon'     => 'fa-triangle-exclamation',
                'label'    => 'Low Stock',
                'href'     => 'inventory/low-stock',
                'isActive' => fn () => uri_string() === 'inventory/low-stock',
            ],
            [
                'icon'     => 'fa-calendar-xmark',
                'label'    => 'Expiring Batches',
                'href'     => 'inventory/expiring',
                'isActive' => fn () => uri_string() === 'inventory/expiring',
            ],
        ],
    ],

    // ---------- 50 ----------
    [
        'priority' => 50,
        'title'    => 'Counselling',
        'gate'     => $isCounsellor,
        'links'    => [
            [
                'icon'     => 'fa-calendar-check',
                'label'    => 'Appointments',
                'href'     => 'counselling',
                'isActive' => fn () => uri_string() === 'counselling',
            ],
            [
                'icon'     => 'fa-clipboard-list',
                'label'    => 'Screenings',
                'href'     => 'counselling/screenings',
                'isActive' => fn () => str_starts_with(uri_string(), 'counselling/screening'),
            ],
            [
                'icon'     => 'fa-bell',
                'label'    => 'Crisis Alerts',
                'href'     => 'counselling/crisis',
                'isActive' => fn () => str_starts_with(uri_string(), 'counselling/crisis'),
            ],
            [
                'icon'     => 'fa-clock',
                'label'    => 'My Availability',
                'href'     => 'counselling/availability',
                'isActive' => fn () => str_starts_with(uri_string(), 'counselling/availability'),
            ],
            [
                'icon'     => 'fa-arrow-right-arrow-left',
                'label'    => 'Referrals',
                'href'     => 'counselling/referrals',
                'isActive' => fn () => str_starts_with(uri_string(), 'counselling/referral'),
            ],
        ],
    ],

    // ---------- 60 ----------
    [
        'priority' => 60,
        'title'    => 'Student',
        'gate'     => $isStudent,
        'links'    => [
            [
                'icon'     => 'fa-calendar-check',
                'label'    => 'My Appointments',
                'href'     => 'dashboard/student#upcoming',
                'isActive' => fn () => false,
            ],
            [
                'icon'     => 'fa-clipboard-list',
                'label'    => 'Screenings',
                'href'     => 'dashboard/student#screenings',
                'isActive' => fn () => false,
            ],
            [
                'icon'     => 'fa-id-card',
                'label'    => 'My Profile',
                'href'     => 'profile',
                'isActive' => fn () => false,
            ],
        ],
    ],

    // ---------- 70 ----------
    [
        'priority' => 70,
        'title'    => 'Facility Operations',
        'gate'     => $isFacilities || $isReportViewer,
        'links'    => [
            [
                'icon'     => 'fa-recycle',
                'label'    => 'BMG Dashboard',
                'href'     => 'dashboard/bmg',
                'isActive' => fn () => uri_string() === 'dashboard/bmg',
            ],
            [
                'icon'     => 'fa-drum',
                'label'    => 'Drums',
                'href'     => 'bmg/drums',
                'isActive' => fn () => str_starts_with(uri_string(), 'bmg/drums'),
            ],
            [
                'icon'     => 'fa-layer-group',
                'label'    => 'Batches',
                'href'     => 'bmg/batches',
                'isActive' => fn () => str_starts_with(uri_string(), 'bmg/batches'),
            ],
            [
                'icon'     => 'fa-tags',
                'label'    => 'Waste Categories',
                'href'     => 'bmg/categories',
                'isActive' => fn () => str_starts_with(uri_string(), 'bmg/categories'),
            ],
            [
                'icon'     => 'fa-chart-line',
                'label'    => 'Reports & Analytics',
                'href'     => 'bmg/reports',
                'isActive' => fn () => str_starts_with(uri_string(), 'bmg/reports'),
            ],
        ],
    ],
];

// Stable sort: lower priority first; ties broken by definition order
// so visual order is preserved when two sections share a priority.
$navSections = array_values($navSections);
foreach ($navSections as $i => $s) {
    $navSections[$i]['_order'] = $i;
}
usort($navSections, fn ($a, $b) => [$a['priority'], $a['_order']] <=> [$b['priority'], $b['_order']]);

foreach ($navSections as $section):
    if (empty($section['gate'])) {
        continue;
    }
?>
    <div class="nav-section-title"><?= esc($section['title']) ?></div>
    <?php foreach ($section['links'] as $link):
        $isLinkActive = (bool) ($link['isActive'])();
        $targetAttr   = ! empty($link['target'])    ? ' target="'    . esc($link['target'])    . '"' : '';
        $dataAttr     = ! empty($link['dataLabel']) ? ' data-label="' . esc($link['dataLabel']) . '"' : '';
    ?>
        <a href="<?= base_url($link['href']) ?>"
           class="nav-link<?= $isLinkActive ? ' active' : '' ?>"<?= $targetAttr . $dataAttr ?>>
            <i class="fas <?= esc($link['icon']) ?>"></i> <?= esc($link['label']) ?>
        </a>
    <?php endforeach; ?>
<?php endforeach; ?>
