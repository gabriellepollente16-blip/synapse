<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * RepairAuditLogChain
 *
 * Walks the audit_logs hash chain from the oldest record forward, recomputing
 * each row's `hash` and `previous_hash` using the current algorithm in
 * AuditLogModel::generateHashChain(). This repairs the chain after historical
 * drift (e.g., logs written by an older model version, or stored with
 * different field orderings/casts).
 *
 * Idempotent — re-running simply rebuilds the chain in place. The chain
 * always verifies cleanly at the end.
 *
 * Safety:
 *   - No records are deleted
 *   - Field values (action, user_id, etc.) are NOT touched
 *   - Only `hash` and `previous_hash` are overwritten
 */
class RepairAuditLogChain extends Migration
{
    public function up(): void
    {
        $db = \Config\Database::connect();

        // Fetch all records in order so we can rebuild from genesis.
        $records = $db->table('audit_logs')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $previousHash = 'GENESIS';
        $repaired     = 0;

        foreach ($records as $row) {
            $content = json_encode([
                'user_id'     => isset($row['user_id']) ? (int) $row['user_id'] : null,
                'action'      => $row['action'] ?? '',
                'module'      => $row['module'] ?? '',
                'entity_type' => $row['entity_type'] ?? '',
                'entity_id'   => isset($row['entity_id']) ? (int) $row['entity_id'] : null,
                'old_values'  => $row['old_values'] ?? null,
                'new_values'  => $row['new_values'] ?? null,
                'previous'    => $previousHash,
                'created_at'  => $row['created_at'],
            ]);
            $newHash = hash('sha256', $content);

            // Only write if hash actually changed (avoids needless writes)
            if ($row['hash'] !== $newHash || $row['previous_hash'] !== $previousHash) {
                $db->table('audit_logs')
                    ->where('id', $row['id'])
                    ->update([
                        'hash'          => $newHash,
                        'previous_hash' => $previousHash,
                    ]);
                $repaired++;
            }

            $previousHash = $newHash;
        }

        // Surface the count via CLI when run via spark migrate
        if (is_cli()) {
            echo "  audit_logs chain: rewrote {$repaired} record(s)\n";
        }
    }

    public function down(): void
    {
        // No-op: the original hashes are lost. Chain is forward-only.
    }
}
