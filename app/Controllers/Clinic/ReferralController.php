<?php

namespace App\Controllers\Clinic;

use App\Controllers\BaseController;
use App\Models\ReferralModel;
use App\Models\NotificationModel;
use App\Models\AuditLogModel;

class ReferralController extends BaseController
{
    protected ReferralModel $referralModel;

    public function __construct()
    {
        $this->referralModel = new ReferralModel();
    }

    /**
     * List referrals.
     */
    public function index()
    {
        $status    = $this->request->getGet('status');
        $direction = $this->request->getGet('direction');

        $referrals = $this->referralModel->getFiltered($status, $direction);

        return view('clinic/referrals/index', [
            'title'     => 'Referrals — SYNAPSE',
            'heading'   => 'Referrals',
            'referrals' => $referrals,
            'filters'   => ['status' => $status, 'direction' => $direction],
        ]);
    }

    /**
     * Create referral form.
     */
    public function create(int $consultationId)
    {
        $consultModel = new \App\Models\ConsultationModel();
        $consult = $consultModel->getFullConsultation($consultationId);

        if ($consult === null) {
            return redirect()->to('/clinic/consultations')->with('error', 'Consultation not found.');
        }

        return view('clinic/referrals/create', [
            'title'   => 'Create Referral — SYNAPSE',
            'heading' => 'Refer to Counselling',
            'consult' => $consult,
        ]);
    }

    /**
     * Store referral.
     */
    public function store()
    {
        $rules = [
            'student_id'              => 'required|is_natural_no_zero',
            'source_consultation_id'  => 'required|is_natural_no_zero',
            'reason'                  => 'required|min_length[3]',
            'priority'                => 'required|in_list[routine,urgent,emergency]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $referralId = $this->referralModel->createClinicReferral([
            'student_id'             => $this->request->getPost('student_id'),
            'referred_by'            => session()->get('user_id'),
            // referred_to is intentionally NULL on creation — the referral is
            // broadcast to all counsellors (via notification). The first
            // counsellor to accept the referral claims ownership by setting
            // referred_to = their user_id. See Counselling\ReferralController::accept.
            'referred_to'            => null,
            'source_consultation_id' => $this->request->getPost('source_consultation_id'),
            'reason'                 => $this->request->getPost('reason'),
            'priority'               => $this->request->getPost('priority'),
        ]);

        if ($referralId) {
            // Auto-generate QR code for this referral
            $token = \App\Libraries\QrCodeGenerator::generateToken();
            $url = \App\Libraries\QrCodeGenerator::buildVerificationUrl($token);
            $qrPath = "writable/bmg_qr_codes/{$token}.svg";
            \App\Libraries\QrCodeGenerator::generatePng($url, $qrPath, 300);

            $this->referralModel->update($referralId, [
                'qr_code_token'   => $token,
                'qr_code_path'    => $qrPath,
                'qr_generated_at' => date('Y-m-d H:i:s'),
            ]);

            // Notify counsellors
            $notifModel = new NotificationModel();
            $notifModel->createNotification(
                null, // broadcast
                'referral',
                'New Referral Received',
                'A new clinic-to-counselling referral has been submitted. Priority: ' . $this->request->getPost('priority'),
                'counselling',
                'referrals',
                $referralId
            );

            $auditModel = new AuditLogModel();
            $auditModel->logAction(session()->get('user_id'), 'create', 'clinic', 'referrals', $referralId);

            $consultId = $this->request->getPost('source_consultation_id');
            return redirect()->to("/clinic/consultations/{$consultId}")
                ->with('success', 'Referral sent to counselling. QR code generated.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to create referral.');
    }

    /**
     * Public endpoint to verify a referral by its QR code token.
     * GET /referral/verify/{token}
     *
     * Returns the referral details so the receiving party can confirm
     * authenticity and view the referral.
     */
    public function verifyQr(string $token)
    {
        if (! \App\Libraries\QrCodeGenerator::isValidToken($token)) {
            return view('clinic/referrals/verify_qr', [
                'title'    => 'Invalid QR Code — SYNAPSE',
                'valid'    => false,
                'error'    => 'Invalid QR code format.',
            ]);
        }

        $referral = $this->referralModel->where('qr_code_token', $token)->first();
        if (!$referral) {
            return view('clinic/referrals/verify_qr', [
                'title'    => 'QR Code Not Found — SYNAPSE',
                'valid'    => false,
                'error'    => 'This QR code is not associated with any active referral.',
            ]);
        }

        // Mark the QR as verified
        $this->referralModel->update($referral['id'], [
            'qr_verified_at' => date('Y-m-d H:i:s'),
            'qr_verified_by' => session()->get('user_id'),
        ]);

        return view('clinic/referrals/verify_qr', [
            'title'     => 'Referral Verified — SYNAPSE',
            'valid'     => true,
            'referral'  => $referral,
        ]);
    }

    /**
     * Download the QR code image for a referral.
     * GET /referral/qr/{id}
     */
    public function downloadQr($id)
    {
        $referral = $this->referralModel->find($id);
        if (!$referral || empty($referral['qr_code_path'])) {
            return $this->response->setStatusCode(404)->setBody('QR code not found.');
        }

        $fullPath = FCPATH . $referral['qr_code_path'];
        if (!file_exists($fullPath)) {
            return $this->response->setStatusCode(404)->setBody('QR code file not found.');
        }

        return $this->response->download($fullPath, null);
    }
}
