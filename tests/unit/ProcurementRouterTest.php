<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\ProcurementRouter;

class ProcurementRouterTest extends CIUnitTestCase
{
    protected $db;
    private ProcurementRouter $router;
    private int $userId      = 99970;
    private int $adminUserId = 99971;
    private int $medIdLow    = 99980;
    private int $medIdHigh   = 99981;
    private int $medIdZero   = 99982;
    private int $batchLow    = 99983;
    private int $batchHigh   = 99984;
    private int $batchZero   = 99985;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
        $this->router = new ProcurementRouter();
        $this->seedFixtures();
        $this->db->transStart();
    }

    protected function tearDown(): void
    {
        if ($this->db->transStatus() !== false) {
            $this->db->transRollback();
        }
        parent::tearDown();
    }

    private function seedFixtures(): void
    {
        // Users
        foreach ([
            [$this->userId,      'proc-router@synapse.edu.ph', 'Proc', 'Router'],
            [$this->adminUserId, 'proc-admin@synapse.edu.ph',  'Proc', 'Admin'],
        ] as $row) {
            [$id, $email, $first, $last] = $row;
            if (!$this->db->table('users')->where('id', $id)->countAllResults()) {
                $this->db->table('users')->insert([
                    'id'            => $id,
                    'email'         => $email,
                    'password_hash' => 'dummy',
                    'user_type'     => 'staff',
                    'first_name'    => $first,
                    'last_name'     => $last,
                    'is_active'     => true,
                ]);
            }
        }

        // Admin role assignment so broadcast notifications have a target.
        $adminRole = $this->db->table('roles')->where('name', 'admin')->get()->getRowArray();
        if ($adminRole && !$this->db->table('user_roles')->where('user_id', $this->adminUserId)->where('role_id', $adminRole['id'])->countAllResults()) {
            $this->db->table('user_roles')->insert([
                'user_id' => $this->adminUserId,
                'role_id' => $adminRole['id'],
            ]);
        }

        // Medicines
        $meds = [
            [$this->medIdLow,  'Mock Low Med',  100],
            [$this->medIdHigh, 'Mock High Med', 100],
            [$this->medIdZero, 'Mock Zero Med', 100],
        ];
        foreach ($meds as $m) {
            [$id, $name, $threshold] = $m;
            if (!$this->db->table('medicines')->where('id', $id)->countAllResults()) {
                $this->db->table('medicines')->insert([
                    'id'                => $id,
                    'generic_name'      => $name,
                    'category'          => 'mock',
                    'unit'              => 'tablet',
                    'reorder_threshold' => $threshold,
                    'is_active'         => true,
                ]);
            }
        }

        // Clear leftover batches and reorder requests so getTotalStock() is
        // deterministic for each fixture medicine.
        $this->db->table('medicine_batches')->where('medicine_id', $this->medIdLow)->delete();
        $this->db->table('medicine_batches')->where('medicine_id', $this->medIdHigh)->delete();
        $this->db->table('medicine_batches')->where('medicine_id', $this->medIdZero)->delete();
        $this->db->table('reorder_requests')->whereIn('medicine_id', [$this->medIdLow, $this->medIdHigh, $this->medIdZero])->delete();
        // Also clear any leftover reorder_request notifications for our
        // fixture medicines.
        $this->db->table('notifications')
            ->where('type', 'reorder_request')
            ->where("JSON_EXTRACT(data, '$.module')", 'inventory')
            ->delete();

        $batches = [
            [$this->batchLow,  $this->medIdLow,  'BATCH-LOW',  50,  50,  'active'],
            [$this->batchHigh, $this->medIdHigh, 'BATCH-HIGH', 500, 500, 'active'],
            [$this->batchZero, $this->medIdZero, 'BATCH-ZERO', 0,   0,   'depleted'],
        ];
        foreach ($batches as $b) {
            [$id, $medId, $num, $recv, $rem, $status] = $b;
            $this->db->table('medicine_batches')->insert([
                'id'                 => $id,
                'medicine_id'        => $medId,
                'batch_number'       => $num,
                'quantity_received'  => $recv,
                'quantity_remaining' => $rem,
                'received_date'      => date('Y-m-d'),
                'expiration_date'    => date('Y-m-d', strtotime('+1 year')),
                'status'             => $status,
            ]);
        }
    }

    public function testReturnsNullWhenStockAboveThreshold(): void
    {
        $this->assertNull($this->router->checkAndReorder($this->medIdHigh, $this->userId));
    }

    public function testReturnsNullWhenMedicineNotFound(): void
    {
        $this->assertNull($this->router->checkAndReorder(99999999, $this->userId));
    }

    public function testCreatesReorderWhenStockBelowThreshold(): void
    {
        $reorderId = $this->router->checkAndReorder($this->medIdLow, $this->userId);
        $this->assertIsInt($reorderId);
        $this->assertGreaterThan(0, $reorderId);

        $row = $this->db->table('reorder_requests')->where('id', $reorderId)->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame((int) $this->medIdLow, (int) $row['medicine_id']);
        $this->assertSame((int) $this->userId,    (int) $row['requested_by']);
        $this->assertSame(50,  (int) $row['current_stock']);
        $this->assertSame(100, (int) $row['reorder_level']);
        $this->assertSame('high',    $row['urgency']); // 50 <= 50% of 100
        $this->assertSame('pending', $row['status']);
    }

    public function testCreatesBroadcastNotification(): void
    {
        $reorderId = $this->router->checkAndReorder($this->medIdLow, $this->userId);
        $this->assertIsInt($reorderId);

        $notes = $this->db->table('notifications')
            ->where('type', 'reorder_request')
            ->where('user_id', $this->adminUserId)
            ->get()->getResultArray();
        $this->assertNotEmpty($notes);

        $matched = false;
        foreach ($notes as $n) {
            $data = json_decode($n['data'] ?? '', true);
            if (is_array($data) && (int) ($data['entity_id'] ?? 0) === $reorderId) {
                $matched = true;
                break;
            }
        }
        $this->assertTrue($matched, 'Expected a reorder_request notification referencing our reorder id');
    }

    public function testDoesNotDuplicateWhenOpenRequestExists(): void
    {
        $first = $this->router->checkAndReorder($this->medIdLow, $this->userId);
        $this->assertIsInt($first);

        // Second call short-circuits and returns null (no-op), because
        // an open request already exists. It must NOT create a duplicate.
        $second = $this->router->checkAndReorder($this->medIdLow, $this->userId);
        $this->assertNull($second);

        $count = $this->db->table('reorder_requests')
            ->where('medicine_id', $this->medIdLow)
            ->whereIn('status', ['pending', 'approved', 'ordered'])
            ->countAllResults();
        $this->assertSame(1, (int) $count);
    }

    public function testUrgencyCriticalWhenStockZero(): void
    {
        $reorderId = $this->router->checkAndReorder($this->medIdZero, $this->userId);
        $this->assertIsInt($reorderId);

        $row = $this->db->table('reorder_requests')->where('id', $reorderId)->get()->getRowArray();
        $this->assertSame('critical', $row['urgency']);
        $this->assertSame(0, (int) $row['current_stock']);
    }

    public function testCheckAllIteratesMedicines(): void
    {
        $created = $this->router->checkAll($this->userId);

        // The return value lists reorder ids created — at minimum the ones
        // triggered by our three fixture medicines (low + zero). Other
        // medicines in the DB may also be low stock from prior test fixtures,
        // so we only assert those three.
        $this->assertGreaterThanOrEqual(2, count($created));

        $rows = $this->db->table('reorder_requests')
            ->whereIn('medicine_id', [$this->medIdLow, $this->medIdHigh, $this->medIdZero])
            ->select('medicine_id, urgency')
            ->get()->getResultArray();

        $byMed = [];
        foreach ($rows as $r) {
            $byMed[(int) $r['medicine_id']] = $r['urgency'];
        }

        // The low one (50% of threshold) and zero one (out of stock) must be
        // re-ordered. The high one (5x threshold) must NOT.
        $this->assertArrayHasKey($this->medIdLow,  $byMed);
        $this->assertArrayHasKey($this->medIdZero, $byMed);
        $this->assertArrayNotHasKey($this->medIdHigh, $byMed);
        $this->assertSame('high',     $byMed[$this->medIdLow]);
        $this->assertSame('critical', $byMed[$this->medIdZero]);
    }
}