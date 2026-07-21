<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\AuditLogModel;

/**
 * @internal
 */
final class AuditLogChainTest extends CIUnitTestCase
{
    protected $db;
    private AuditLogModel $audit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
        $this->audit = new AuditLogModel();

        // Sandbox inside a transaction; rolled back in tearDown.
        $this->db->transStart();

        // audit_logs.user_id has a FK to users.id. Tests below reference
        // users 99990 and 99991, so create them if they don't exist.
        // Create the user fixtures that the tests below reference.
        // Multiple tests use 99990..99994 to avoid colliding with real seeded
        // user IDs in the test DB.
        $fixtureIds = [99990, 99991, 99992, 99993, 99994];
        $existing = $this->db->table('users')->whereIn('id', $fixtureIds)->get()->getResultArray();
        $have = array_column($existing, 'id');
        foreach ($fixtureIds as $uid) {
            if (!in_array($uid, $have, true)) {
                $this->db->table('users')->insert([
                    'id'            => $uid,
                    'email'         => 'audit-test-' . $uid . '@synapse.edu.ph',
                    'password_hash' => 'x',
                    'first_name'    => 'Audit',
                    'last_name'     => 'Test',
                    'is_active'     => true,
                ]);
            }
        }
    }

    protected function tearDown(): void
    {
        $this->db->transRollback();
        parent::tearDown();
    }

    public function testEmptyChainVerifiesCleanly(): void
    {
        // With nothing inserted in this transaction, there are zero rows
        // and the chain is trivially intact.
        $result = $this->audit->verifyChainIntegrity(100);
        $this->assertTrue($result['intact']);
        $this->assertSame(0, $result['error_count']);
    }

    public function testInsertedLogVerifiesInChain(): void
    {
        // Insert one audit log and verify the chain (genesis -> record).
        $ok = $this->audit->insert([
            'user_id'     => 99990,
            'action'      => 'login_success',
            'module'      => 'auth',
            'entity_type' => 'users',
            'entity_id'   => 99990,
            'old_values'  => null,
            'new_values'  => null,
            'ip_address'  => '127.0.0.1',
            'user_agent'  => 'PHPUnit',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $this->assertNotFalse($ok, 'Insert should succeed');

        $result = $this->audit->verifyChainIntegrity(100);
        $this->assertTrue($result['intact'], 'Chain should be intact for a single inserted log');
        $this->assertSame(1, $result['checked']);
        $this->assertSame(0, $result['error_count']);
    }

    public function testMultipleLogsChainInOrder(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->audit->insert([
                'user_id'     => 99990 + $i,
                'action'      => 'create',
                'module'      => 'auth',
                'entity_type' => 'users',
                'entity_id'   => 99990 + $i,
                'old_values'  => null,
                'new_values'  => json_encode(['iteration' => $i]),
                'ip_address'  => '127.0.0.1',
                'user_agent'  => 'PHPUnit',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        $result = $this->audit->verifyChainIntegrity(100);
        $this->assertTrue($result['intact']);
        $this->assertSame(5, $result['checked']);
        $this->assertSame(0, $result['error_count']);
    }

    public function testTamperedRecordBreaksChain(): void
    {
        // Insert one log legitimately, then tamper with its hash.
        $this->audit->insert([
            'user_id'     => 99990,
            'action'      => 'login_success',
            'module'      => 'auth',
            'entity_type' => 'users',
            'entity_id'   => 99990,
            'ip_address'  => '127.0.0.1',
            'user_agent'  => 'PHPUnit',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        // Corrupt the hash directly (bypassing the model callback).
        $this->db->table('audit_logs')
            ->where('user_id', 99990)
            ->update(['hash' => str_repeat('0', 64)]);

        $result = $this->audit->verifyChainIntegrity(100);
        $this->assertFalse($result['intact']);
        $this->assertGreaterThanOrEqual(1, $result['error_count']);
    }

    public function testBrokenLinkDetected(): void
    {
        $this->audit->insert([
            'user_id'     => 99990,
            'action'      => 'login_success',
            'module'      => 'auth',
            'entity_type' => 'users',
            'entity_id'   => 99990,
            'ip_address'  => '127.0.0.1',
            'user_agent'  => 'PHPUnit',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $this->audit->insert([
            'user_id'     => 99991,
            'action'      => 'logout',
            'module'      => 'auth',
            'entity_type' => 'users',
            'entity_id'   => 99990,
            'ip_address'  => '127.0.0.1',
            'user_agent'  => 'PHPUnit',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        // Break the chain by rewriting previous_hash on the second record.
        $this->db->table('audit_logs')
            ->where('user_id', 99991)
            ->update(['previous_hash' => str_repeat('f', 64)]);

        $result = $this->audit->verifyChainIntegrity(100);
        $this->assertFalse($result['intact']);
    }
}
