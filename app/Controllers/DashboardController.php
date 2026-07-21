<?php

namespace App\Controllers;

class DashboardController extends BaseController
{

    /**
     * Load screening templates safely. Returns []. The
     * assessment_templates table was dropped by migrations so
     * the student dashboard renders an empty list rather than 500.
     */
    private function safeTemplates($db): array
    {
        try {
            return $db->table('assessment_templates')
                ->where('is_active', true)
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('info', 'DashboardController::safeTemplates skipped missing table');
            return [];
        }
    }


    /**
     * Count rows in a table safely. Returns 0 if the table is missing
     * (e.g. dropped by migrations). Lets dashboards render even when
     * an optional capability table has been removed.
     */
    private function safeCount($db, string $table, array $where = []): int
    {
        try {
            $builder = $db->table($table);
            foreach ($where as $col => $val) {
                $builder = $builder->where($col, $val);
            }
            return (int) $builder->countAllResults();
        } catch (\Throwable $e) {
            log_message('info', 'safeCount skipped missing table: ' . $table);
            return 0;
        }
    }
    /**
     * Main dashboard — cross-module overview.
     * Shows a single page summarizing all tabs (Clinic, Counselling, BMG,
     * Reports, etc.) so users can scan the whole platform at a glance.
     * Each module card only renders if the user's role is allowed to see it.
     */
    public function index()
    {
        $db = \Config\Database::connect();
        $primaryRole = session()->get('primary_role');
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

        // -------- CLINIC module summary --------
        $clinicVisible = in_array($primaryRole, ['admin', 'clinic_staff'], true);
        $clinicKpis = [
            'consultations_30d' => 0,
            'triage_high'       => 0,
            'referrals_30d'     => 0,
            'low_stock'         => 0,
            'top_complaint'     => null,
        ];
        $clinicSummary = null;
        if ($clinicVisible) {
            $clinicKpis['consultations_30d'] = (int) $db->table('consultations')
                ->where('consultation_date >=', $thirtyDaysAgo)
                ->countAllResults(false);
            $clinicKpis['triage_high'] = (int) $db->table('consultations')
                ->where('consultation_date >=', $thirtyDaysAgo)
                ->whereIn('triage_priority', ['high', 'urgent'])
                ->countAllResults(false);
            $clinicKpis['referrals_30d'] = (int) $db->table('referrals')
                ->where('created_at >=', $thirtyDaysAgo)
                ->countAllResults(false);
            $medicineModel = new \App\Models\MedicineModel();
            $clinicKpis['low_stock'] = count($medicineModel->getLowStock());
            $complaintRow = $db->table('consultations')
                ->where('consultation_date >=', $thirtyDaysAgo)
                ->select('chief_complaint, COUNT(id) as cnt')
                ->groupBy('chief_complaint')
                ->orderBy('cnt', 'DESC')
                ->limit(1)
                ->get()->getRowArray();
            $clinicKpis['top_complaint'] = $complaintRow['chief_complaint'] ?? null;
        }

        // -------- COUNSELLING module summary --------
        $counsellingVisible = in_array($primaryRole, ['admin', 'counsellor'], true);
        $counsellingKpis = [
            'appointments_30d' => 0,
            'no_shows'         => 0,
            'crisis_alerts'    => 0,
            'severe_screening' => 0,
        ];
        if ($counsellingVisible) {
            $counsellingKpis['appointments_30d'] = (int) $db->table('counselling_appointments')
                ->where('appointment_date >=', $thirtyDaysAgo)
                ->countAllResults(false);
            $counsellingKpis['no_shows'] = (int) $db->table('counselling_appointments')
                ->where('appointment_date >=', $thirtyDaysAgo)
                ->where('status', 'no_show')
                ->countAllResults(false);
            $counsellingKpis['crisis_alerts']    = $this->safeCount($db, 'crisis_alerts',          ['created_at >=' => $thirtyDaysAgo]);
            $counsellingKpis['severe_screening'] = $this->safeCount($db, 'assessment_responses', ['submitted_at >=' => $thirtyDaysAgo, 'total_score >=' => 15]);
        }

        // -------- BMG / FACILITY OPERATIONS module summary --------
        // BMG-only KPIs. No cross-module data is loaded here — only
        // Composting/Drum/Batch/Input/Output tables are queried.
        $bmgVisible = in_array($primaryRole, ['admin', 'facilities_staff', 'report_viewer'], true);
        $bmgKpis = [
            'total_drums'    => 0,
            'idle_drums'     => 0,
            'processing'     => 0,
            'maintenance'    => 0,
            'input_30d_kg'   => 0.0,
            'output_30d_kg'  => 0.0,
            'avg_yield_pct'  => 0.0,
            'avg_duration_days'  => 0,
            'avg_mass_reduction' => 0.0,
            'active_batches' => 0,
            'idle_alerts'    => 0,
        ];
        $activeBatches  = [];
        $completedRecent = [];
        if ($bmgVisible) {
            $drumModel   = new \App\Models\BmgDrumModel();
            $batchModel  = new \App\Models\BmgBatchModel();
            $inputModel  = new \App\Models\BmgInputModel();
            $outputModel = new \App\Models\BmgOutputModel();

            $statusCounts = $drumModel->getStatusCounts();
            $bmgKpis['total_drums'] = (int) array_sum($statusCounts);
            $bmgKpis['idle_drums']  = (int) ($statusCounts['idle']        ?? 0);
            $bmgKpis['processing']  = (int) ($statusCounts['processing']  ?? 0);
            $bmgKpis['maintenance'] = (int) ($statusCounts['maintenance'] ?? 0);

            $bmgKpis['input_30d_kg']  = (float) ($inputModel
                ->where('recorded_at >=', $thirtyDaysAgo . ' 00:00:00')
                ->selectSum('weight_kg')
                ->get()->getRowArray()['weight_kg'] ?? 0);
            $bmgKpis['output_30d_kg'] = (float) $outputModel->getTotalOutputSince($thirtyDaysAgo);

            $completedBatches = $batchModel
                ->where('status', 'completed')
                ->where('completion_date >=', $thirtyDaysAgo)
                ->orderBy('completion_date', 'DESC')
                ->limit(10)
                ->findAll();
            $completedRecent = array_slice($completedBatches, 0, 5);
            if (count($completedBatches) > 0) {
                $sumYield = 0;
                $sumDuration = 0;
                $sumMassReduction = 0;
                $countedDuration = 0;
                $countedMass = 0;
                foreach ($completedBatches as $b) {
                    if (! empty($b['yield_percentage'])) {
                        $sumYield += (float) $b['yield_percentage'];
                    }
                    if (! empty($b['duration_days'])) {
                        $sumDuration += (int) $b['duration_days'];
                        $countedDuration++;
                    }
                    if (isset($b['mass_reduction_pct']) && $b['mass_reduction_pct'] !== null) {
                        $sumMassReduction += (float) $b['mass_reduction_pct'];
                        $countedMass++;
                    }
                }
                $bmgKpis['avg_yield_pct']      = round($sumYield / count($completedBatches), 1);
                $bmgKpis['avg_duration_days']  = $countedDuration > 0 ? (int) round($sumDuration / $countedDuration) : 0;
                $bmgKpis['avg_mass_reduction'] = $countedMass > 0 ? round($sumMassReduction / $countedMass, 1) : 0.0;
            }

            $activeBatches  = $batchModel->getActiveBatches();
            $bmgKpis['active_batches'] = count($activeBatches);
            $bmgKpis['idle_alerts'] = count($drumModel->getIdleForMoreThan(30));
        }

        // -------- RECENT ACTIVITY (any role) --------
        $recentActivity = $db->table('audit_logs')
            ->orderBy('created_at', 'DESC')
            ->limit(8)
            ->get()->getResultArray();

        // -------- NOTIFICATIONS (any role) --------
        // Per-user notifications from the notifications table.
        // Falls back to an empty array if the table is unavailable
        // or the user has no notifications — never break the page.
        $notifications     = [];
        $unreadNotifCount  = 0;
        $userId            = (int) session()->get('user_id');
        if ($userId > 0) {
            try {
                $notifications    = $db->table('notifications')
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit(6)
                    ->get()->getResultArray();
                $unreadNotifCount = (int) $db->table('notifications')
                    ->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->countAllResults();
            } catch (\Throwable $e) {
                log_message('warning', 'Dashboard notifications query failed: ' . $e->getMessage());
            }
        }

        // -------- PENDING TASKS (cross-module approximation) --------
        // Count of items needing user attention right now:
        //   - unread notifications
        //   - active BMG batches (if BMG visible) that are overdue
        //   - pending clinic referrals (if clinic visible)
        $pendingTasks = $unreadNotifCount;
        if ($bmgVisible && ! empty($activeBatches)) {
            $now = date('Y-m-d');
            foreach ($activeBatches as $ab) {
                if (! empty($ab['expected_completion_date']) && $ab['expected_completion_date'] < $now) {
                    $pendingTasks++;
                }
            }
        }
        if ($clinicVisible) {
            try {
                $pendingRefCount = (int) $db->table('referrals')
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->countAllResults();
                $pendingTasks += $pendingRefCount;
            } catch (\Throwable $e) {
                // referrals table may not exist in all environments
            }
        }

        return view('dashboard/overview', [
            'title'               => 'Dashboard — SYNAPSE',
            'heading'             => 'Dashboard',
            'primaryRole'         => $primaryRole,
            'clinicVisible'       => $clinicVisible,
            'clinicKpis'          => $clinicKpis,
            'counsellingVisible'  => $counsellingVisible,
            'counsellingKpis'     => $counsellingKpis,
            'bmgVisible'          => $bmgVisible,
            'bmgKpis'             => $bmgKpis,
            'activeBatches'       => $activeBatches,
            'completedRecent'     => $completedRecent,
            'recentActivity'      => $recentActivity,
            'notifications'       => $notifications,
            'unreadNotifCount'    => $unreadNotifCount,
            'pendingTasks'        => $pendingTasks,
        ]);
    }

    /**
     * Admin Dashboard.
     */
    public function admin()
    {
        $db = \Config\Database::connect();
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $today = date('Y-m-d');

        // Fetch Clinic summaries
        $totalClinic = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->countAllResults(false);
        $triageHigh = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->where('triage_priority', 'high')->countAllResults(false);
        $triageUrgent = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->where('triage_priority', 'urgent')->countAllResults(false);
        $referrals = $db->table('referrals')->where('created_at >=', $thirtyDaysAgo)->countAllResults(false);
        $complaintRow = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->select('chief_complaint, COUNT(id) as cnt')->groupBy('chief_complaint')->orderBy('cnt', 'DESC')->limit(1)->get()->getRowArray();
        
        $clinicData = [
            'total_consultations' => $totalClinic,
            'triage_high'         => $triageHigh,
            'triage_urgent'       => $triageUrgent,
            'referrals_count'     => $referrals,
            'top_complaint'       => $complaintRow ? $complaintRow['chief_complaint'] : 'general check-up',
        ];

        $summarizer = new \App\Libraries\ReportSummarizer();
        $clinicSummary = $summarizer->generateSummary('clinic', $thirtyDaysAgo, $today, $clinicData, null, session()->get('user_id'));

        // Fetch Counselling summaries
        $totalAppts = $db->table('counselling_appointments')->where('appointment_date >=', $thirtyDaysAgo)->countAllResults(false);
        $noShows = $db->table('counselling_appointments')->where('appointment_date >=', $thirtyDaysAgo)->where('status', 'no_show')->countAllResults(false);
        $crisisAlerts     = $this->safeCount($db, 'crisis_alerts',         ['created_at >=' => $thirtyDaysAgo]);
        $severeScreenings = $this->safeCount($db, 'assessment_responses', ['submitted_at >=' => $thirtyDaysAgo, 'total_score >=' => 15]);

        $counsellData = [
            'total_appointments'      => $totalAppts,
            'total_no_shows'          => $noShows,
            'crisis_alerts_count'     => $crisisAlerts,
            'severe_screenings_count' => $severeScreenings,
        ];
        $counsellingSummary = $summarizer->generateSummary('counselling', $thirtyDaysAgo, $today, $counsellData, null, session()->get('user_id'));

        // Dashboard stats
        $totalUsers = $db->table('users')->countAllResults();
        $consultationsToday = $db->table('consultations')->where('DATE(consultation_date)', date('Y-m-d'))->countAllResults();
        
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        $appointmentsThisWeek = $db->table('counselling_appointments')
            ->where('appointment_date >=', $startOfWeek)
            ->where('appointment_date <=', $endOfWeek)
            ->countAllResults();

        $medicineModel = new \App\Models\MedicineModel();
        $lowStockMedicines = count($medicineModel->getLowStock());

        return view('dashboard/admin', [
            'title'                => 'Admin Dashboard — SYNAPSE',
            'heading'              => 'System Administration',
            'clinicSummary'        => $clinicSummary['summary_text'],
            'counsellingSummary'   => $counsellingSummary['summary_text'],
            'totalUsers'           => $totalUsers,
            'consultationsToday'   => $consultationsToday,
            'appointmentsThisWeek' => $appointmentsThisWeek,
            'lowStockMedicines'    => $lowStockMedicines,
        ]);
    }

    /**
     * Clinic Staff Dashboard.
     */
    public function clinic()
    {
        $db = \Config\Database::connect();
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $today = date('Y-m-d');

        $totalClinic = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->countAllResults(false);
        $triageHigh = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->where('triage_priority', 'high')->countAllResults(false);
        $triageUrgent = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->where('triage_priority', 'urgent')->countAllResults(false);
        $referrals = $db->table('referrals')->where('created_at >=', $thirtyDaysAgo)->countAllResults(false);
        $complaintRow = $db->table('consultations')->where('consultation_date >=', $thirtyDaysAgo)->select('chief_complaint, COUNT(id) as cnt')->groupBy('chief_complaint')->orderBy('cnt', 'DESC')->limit(1)->get()->getRowArray();
        
        $clinicData = [
            'total_consultations' => $totalClinic,
            'triage_high'         => $triageHigh,
            'triage_urgent'       => $triageUrgent,
            'referrals_count'     => $referrals,
            'top_complaint'       => $complaintRow ? $complaintRow['chief_complaint'] : 'general check-up',
        ];

        $summarizer = new \App\Libraries\ReportSummarizer();
        $summary = $summarizer->generateSummary('clinic', $thirtyDaysAgo, $today, $clinicData, null, session()->get('user_id'));

        // Dashboard stats
        $consultationModel = new \App\Models\ConsultationModel();
        $todayStats = $consultationModel->getTodayStats();
        
        $medicineModel = new \App\Models\MedicineModel();
        $lowStockAlerts = count($medicineModel->getLowStock());

        return view('dashboard/clinic', [
            'title'             => 'Clinic Dashboard — SYNAPSE',
            'heading'           => 'Clinic Management',
            'aiSummary'         => $summary['summary_text'],
            'patientsToday'     => $todayStats['total'],
            'completedConsults' => $todayStats['completed'],
            'inProgress'        => $todayStats['in_progress'],
            'lowStockAlerts'    => $lowStockAlerts,
        ]);
    }

    /**
     * Counsellor Dashboard.
     */
    public function counsellor()
    {
        $db = \Config\Database::connect();
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $today = date('Y-m-d');

        $totalAppts = $db->table('counselling_appointments')->where('appointment_date >=', $thirtyDaysAgo)->countAllResults(false);
        $noShows = $db->table('counselling_appointments')->where('appointment_date >=', $thirtyDaysAgo)->where('status', 'no_show')->countAllResults(false);
        $crisisAlerts     = $this->safeCount($db, 'crisis_alerts',         ['created_at >=' => $thirtyDaysAgo]);
        $severeScreenings = $this->safeCount($db, 'assessment_responses', ['submitted_at >=' => $thirtyDaysAgo, 'total_score >=' => 15]);

        $counsellData = [
            'total_appointments'      => $totalAppts,
            'total_no_shows'          => $noShows,
            'crisis_alerts_count'     => $crisisAlerts,
            'severe_screenings_count' => $severeScreenings,
        ];

        $summarizer = new \App\Libraries\ReportSummarizer();
        $summary = $summarizer->generateSummary('counselling', $thirtyDaysAgo, $today, $counsellData, null, session()->get('user_id'));

        // Dashboard stats
        $counsellorId = session()->get('user_id');
        $appointmentModel = new \App\Models\CounsellingAppointmentModel();
        $todayStats = $appointmentModel->getTodayStats($counsellorId);

        $crisisAlertModel = new \App\Models\CrisisAlertModel();
        $crisisAlertsCount = count($crisisAlertModel->getActive());

        $referralModel = new \App\Models\ReferralModel();
        $pendingReferrals = count($referralModel->getPending('clinic_to_counselling'));

        $activeCaseload = $db->table('counselling_appointments')
            ->select('COUNT(DISTINCT student_id) as count')
            ->where('counsellor_id', $counsellorId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->get()->getRowArray()['count'] ?? 0;

        return view('dashboard/counsellor', [
            'title'             => 'Counselling Dashboard — SYNAPSE',
            'heading'           => 'Counselling Management',
            'aiSummary'         => $summary['summary_text'],
            'appointmentsToday' => $todayStats['total'],
            'crisisAlerts'      => $crisisAlertsCount,
            'pendingReferrals'  => $pendingReferrals,
            'activeCaseload'    => $activeCaseload,
        ]);
    }

    /**
     * Student Dashboard.
     */
    public function student()
    {
        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $student = $db->table('students')->where('user_id', $userId)->get()->getRowArray();

        $stats = [
            'appointments'  => 0,
            'consultations' => 0,
            'hours'         => 0,
        ];
        $upcoming = [];
        $templates = [];
        $activeQueue = null;   // today's in-clinic queue row (if any)
        $queueAhead  = 0;      // how many people are ahead of them

        if ($student) {
            $stats['appointments'] = $db->table('counselling_appointments')
                ->where('student_id', $student['id'])
                ->countAllResults();

            $stats['consultations'] = $db->table('consultations')
                ->where('student_id', $student['id'])
                ->countAllResults();

            // outreach_attendance is an optional table — only the new
            // outreach module populates it. Older installs won't have it,
            // so we silently fall back to 0 hours.
            try {
                $hoursRow = $db->table('outreach_attendance')
                    ->where('user_id', $userId)
                    ->selectSum('hours_credited')
                    ->get()->getRowArray();
                $stats['hours'] = (float) ($hoursRow['hours_credited'] ?? 0);
            } catch (\Throwable $e) {
                $stats['hours'] = 0.0;
            }

            // Fetch upcoming appointments with counsellor names
            $upcoming = $db->table('counselling_appointments')
                ->select('counselling_appointments.*, users.first_name, users.last_name')
                ->join('users', 'users.id = counselling_appointments.counsellor_id')
                ->where('student_id', $student['id'])
                ->where('appointment_date >=', date('Y-m-d'))
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->orderBy('appointment_date', 'ASC')
                ->orderBy('start_time', 'ASC')
                ->get()->getResultArray();

            $templates = $this->safeTemplates($db);

            /* Live queue banner — if this student has an active
               consultation today (waiting / called / in_session), show
               their number and how many people are ahead of them.
               Uses MySQL CURDATE() so it matches the kiosk's day
               regardless of PHP's timezone. */
            $activeQueue = $db->table('consultations')
                ->where('student_id', $student['id'])
                ->where('DATE(consultation_date) = CURDATE()', null, false)
                ->whereIn('status', ['in_progress', 'called', 'in_session'])
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            if ($activeQueue && $activeQueue['status'] === 'in_progress' && $activeQueue['queue_position'] !== null) {
                /* People ahead = everyone with a smaller queue_position
                   AND status = in_progress. (Called/in_session are
                   already being seen or about to be.) */
                $queueAhead = $db->table('consultations')
                    ->where('DATE(consultation_date) = CURDATE()', null, false)
                    ->where('status', 'in_progress')
                    ->where('queue_position <', (int) $activeQueue['queue_position'])
                    ->countAllResults();
            }
        }

        return view('dashboard/student', [
            'title'       => 'Student Portal — SYNAPSE',
            'heading'     => 'Student Portal',
            'student'     => $student,
            'stats'       => $stats,
            'upcoming'    => $upcoming,
            'templates'   => $templates,
            'activeQueue' => $activeQueue,
            'queueAhead'  => $queueAhead,
        ]);
    }

    /**
     * BMG (Biodegradable Waste Management) Dashboard.
     * Used by facilities_staff and admin roles.
     */
    public function bmg()
    {
        $drumModel    = new \App\Models\BmgDrumModel();
        $batchModel   = new \App\Models\BmgBatchModel();
        $inputModel   = new \App\Models\BmgInputModel();
        $outputModel  = new \App\Models\BmgOutputModel();

        // Drum status counts
        $statusCounts = $drumModel->getStatusCounts();

        // Active batches
        $activeBatches = $batchModel->getActiveBatches();

        // Recent completed batches (last 30 days). The view shows drum_code
        // and drum_name columns, so join through bmg_drums.
        $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
        $completedBatches = $batchModel
            ->select('bmg_batches.*, bmg_drums.drum_code, bmg_drums.name AS drum_name')
            ->join('bmg_drums', 'bmg_drums.id = bmg_batches.drum_id', 'left')
            ->where('bmg_batches.status', 'completed')
            ->where('bmg_batches.completion_date >=', $thirtyDaysAgo)
            ->orderBy('bmg_batches.completion_date', 'DESC')
            ->limit(10)
            ->findAll();

        // Aggregate metrics
        $totalInputLast30 = $inputModel
            ->where('recorded_at >=', $thirtyDaysAgo . ' 00:00:00')
            ->selectSum('weight_kg')
            ->get()
            ->getRowArray()['weight_kg'] ?? 0;

        $totalOutputLast30 = $outputModel->getTotalOutputSince($thirtyDaysAgo);

        $avgYield = 0.0;
        $completedCount = count($batchModel->where('status', 'completed')
            ->where('completion_date >=', $thirtyDaysAgo)->findAll());
        if ($completedCount > 0) {
            $sumYield = 0;
            foreach ($completedBatches as $b) {
                if ($b['yield_percentage']) $sumYield += (float) $b['yield_percentage'];
            }
            $avgYield = round($sumYield / $completedCount, 1);
        }

        // Idle drums (potential issue alerts)
        $idleAlerts = $drumModel->getIdleForMoreThan(30);

        return view('dashboard/bmg', [
            'title'             => 'BMG Dashboard — SYNAPSE',
            'heading'           => 'BMG Dashboard',
            'statusCounts'      => $statusCounts,
            'activeBatches'     => $activeBatches,
            'completedBatches'  => $completedBatches,
            'totalInputLast30'  => (float) $totalInputLast30,
            'totalOutputLast30' => (float) $totalOutputLast30,
            'avgYield'          => $avgYield,
            'idleAlerts'        => $idleAlerts,
        ]);
    }

    /**
     * Facility Operations Dashboard.
     * Combined admin + facilities view with cross-module ops metrics.
     * Shows all BMG drums, active batches, and analytics.
     */
    public function facilityOperations()
    {
        // Reuse BMG dashboard for the Facility Operations tab
        return $this->bmg();
    }

    /**
     * Reports Dashboard.
     * Used by report_viewer role — read-only cross-module reports.
     */
    public function reports()
    {
        return view('dashboard/reports', [
            'title'   => 'Reports — SYNAPSE',
            'heading' => 'Reports Dashboard',
        ]);
    }

    /**
     * Employee Portal Dashboard.
     * Used by employee role — view own records, submit referrals,
     * check own clinic consultations, and see recent notifications.
     *
     * Mirrors the Student dashboard structure: stats-grid KPI tiles,
     * profile card on the left, quick actions / notifications on the
     * right, plus full-width tables for "My Submitted Referrals" and
     * "My Recent Consultations".
     */
    public function employee()
    {
        $db = \Config\Database::connect();
        $userId = (int) session()->get('user_id');

        $employeeModel = new \App\Models\EmployeeModel();
        $employee = $employeeModel->where('user_id', $userId)->first();

        // -------- KPI STATS --------
        //   referrals_submitted  — total referrals this employee has filed
        //   referrals_pending    — open referrals (pending / in_progress)
        //   consultations         — own clinic visits (scoped via the
        //                           polymorphic patient_type / employee_id
        //                           columns added in migration 000004)
        $stats = [
            'referrals_submitted' => 0,
            'referrals_pending'   => 0,
            'consultations'       => 0,
        ];

        if ($employee !== null) {
            $stats['referrals_submitted'] = (int) $db->table('referrals')
                ->where('referred_by', $userId)
                ->countAllResults();

            $stats['referrals_pending'] = (int) $db->table('referrals')
                ->where('referred_by', $userId)
                ->whereIn('status', ['pending', 'in_progress'])
                ->countAllResults();

            $stats['consultations'] = (int) $db->table('consultations')
                ->where('patient_type', 'employee')
                ->where('employee_id', (int) $employee['id'])
                ->countAllResults();
        }

        // -------- MY SUBMITTED REFERRALS --------
        // Reuses the same join shape as clinic/referrals/index.php
        // (students + users x2) so the on-screen data is byte-identical
        // to what clinic staff see in their queue.
        $myReferrals = $db->table('referrals')
            ->select('referrals.*, students.student_number, u_student.first_name AS student_first, u_student.last_name AS student_last')
            ->join('students', 'students.id = referrals.student_id')
            ->join('users AS u_student', 'u_student.id = students.user_id')
            ->where('referrals.referred_by', $userId)
            ->orderBy('referrals.created_at', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        // -------- MY RECENT CONSULTATIONS --------
        // Joins users for the attending staff's name. Scoped to the
        // employee row via patient_type='employee' + employee_id so
        // student visits don't bleed into an employee's record.
        $myConsultations = [];
        if ($employee !== null) {
            $myConsultations = $db->table('consultations')
                ->select('consultations.id, consultations.chief_complaint, consultations.diagnosis, consultations.status, consultations.consultation_date, users.first_name AS staff_first, users.last_name AS staff_last')
                ->join('users', 'users.id = consultations.attending_user_id')
                ->where('consultations.patient_type', 'employee')
                ->where('consultations.employee_id', (int) $employee['id'])
                ->orderBy('consultations.consultation_date', 'DESC')
                ->limit(10)
                ->get()
                ->getResultArray();
        }

        // -------- NOTIFICATIONS PREVIEW --------
        // Same query pattern as DashboardController::index() — per-user,
        // most recent 6, with unread count. Wrapped in try/catch so a
        // missing notifications table never 500s the dashboard.
        $notifications    = [];
        $unreadNotifCount = 0;
        if ($userId > 0) {
            try {
                $notifications = $db->table('notifications')
                    ->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit(6)
                    ->get()
                    ->getResultArray();

                $unreadNotifCount = (int) $db->table('notifications')
                    ->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->countAllResults();
            } catch (\Throwable $e) {
                log_message('warning', 'Employee dashboard notifications query failed: ' . $e->getMessage());
            }
        }

        return view('dashboard/employee', [
            'title'            => 'Employee Portal — SYNAPSE',
            'heading'          => 'Employee Portal',
            'employee'         => $employee,
            'stats'            => $stats,
            'myReferrals'      => $myReferrals,
            'myConsultations'  => $myConsultations,
            'notifications'    => $notifications,
            'unreadNotifCount' => $unreadNotifCount,
        ]);
    }
}
