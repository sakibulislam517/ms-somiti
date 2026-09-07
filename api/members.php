<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function member_balance(PDO $db): array
{
    return [
        'collections' => (float)$db->query("SELECT COALESCE(SUM(debit),0) FROM investor_ledger WHERE entry_type = 'collection'")->fetchColumn(),
        'investments' => (float)$db->query("SELECT COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) FROM investor_ledger WHERE entry_type IN ('investment','invest_withdraw')")->fetchColumn(),
        'payments' => (float)$db->query("SELECT COALESCE(SUM(credit),0) FROM investor_ledger WHERE entry_type IN ('payment','expense')")->fetchColumn(),
        'total_in' => (float)$db->query("SELECT COALESCE(SUM(debit),0) FROM investor_ledger")->fetchColumn(),
        'total_out' => (float)$db->query("SELECT COALESCE(SUM(credit),0) FROM investor_ledger")->fetchColumn(),
    ];
}

try {
    $db = somiti_db();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'summary') {
            somiti_json(['ok' => true, 'data' => member_balance($db)]);
        }

        if ($action === 'balances') {
            $rows = $db->query("SELECT m.id, m.name, m.phone, m.address, m.status,
                COALESCE(SUM(CASE WHEN l.entry_type = 'collection' THEN l.debit ELSE 0 END),0) AS collection_total,
                COALESCE(SUM(CASE WHEN l.entry_type IN ('investment') THEN l.debit ELSE 0 END),0)
                  - COALESCE(SUM(CASE WHEN l.entry_type IN ('invest_withdraw') THEN l.credit ELSE 0 END),0) AS invest_balance,
                COALESCE(SUM(CASE WHEN l.entry_type IN ('payment','expense') THEN l.credit ELSE 0 END),0) AS payment_total,
                COALESCE(SUM(l.debit),0) - COALESCE(SUM(l.credit),0) AS net_balance
                FROM members m
                LEFT JOIN investor_ledger l ON l.member_id = m.id
                WHERE m.status = 1
                GROUP BY m.id, m.name, m.phone, m.address, m.status
                ORDER BY m.name")->fetchAll();
            foreach ($rows as &$row) {
                $row['collection_total'] = (float)$row['collection_total'];
                $row['invest_balance'] = (float)$row['invest_balance'];
                $row['payment_total'] = (float)$row['payment_total'];
                $row['net_balance'] = (float)$row['net_balance'];
            }
            somiti_json(['ok' => true, 'data' => $rows]);
        }

        if (($action === 'edit' || $action === 'get') && isset($_GET['id'])) {
            $stmt = $db->prepare('SELECT * FROM members WHERE id = ?');
            $stmt->execute([(int)$_GET['id']]);
            $row = $stmt->fetch();
            if (!$row) {
                somiti_json(['ok' => false, 'message' => 'Member not found.'], 404);
            }
            somiti_json(['ok' => true, 'data' => $row]);
        }

        $stmt = $db->query('SELECT id, name, phone, address, status FROM members ORDER BY name');
        somiti_json(['ok' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($method !== 'POST') {
        somiti_json(['ok' => false, 'message' => 'Method not allowed.'], 405);
    }

    $input = $_POST;
    if (!$input) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    }
    $action = $input['action'] ?? 'save';

    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id < 1) {
            somiti_json(['ok' => false, 'message' => 'Invalid member id.'], 422);
        }
        $db->prepare('UPDATE members SET status = 0 WHERE id = ?')->execute([$id]);
        somiti_json(['ok' => true, 'message' => 'Member removed.']);
    }

    if ($action === 'restore') {
        $id = (int)($input['id'] ?? 0);
        if ($id < 1) {
            somiti_json(['ok' => false, 'message' => 'Invalid member id.'], 422);
        }
        $db->prepare('UPDATE members SET status = 1 WHERE id = ?')->execute([$id]);
        somiti_json(['ok' => true, 'message' => 'Member restored.']);
    }

    $name = trim((string)($input['name'] ?? ''));
    if ($name === '') {
        somiti_json(['ok' => false, 'message' => 'Member name is required.'], 422);
    }
    $id = (int)($input['id'] ?? 0);
    $fields = [
        'name' => $name,
        'phone' => trim((string)($input['phone'] ?? '')) ?: null,
        'address' => trim((string)($input['address'] ?? '')) ?: null,
    ];

    if ($id > 0) {
        $db->prepare('UPDATE members SET name = ?, phone = ?, address = ? WHERE id = ?')
            ->execute([$fields['name'], $fields['phone'], $fields['address'], $id]);
        somiti_json(['ok' => true, 'message' => 'Member updated successfully.']);
    }

    $stmt = $db->prepare('INSERT INTO members (name, phone, address) VALUES (?, ?, ?)');
    $stmt->execute([$fields['name'], $fields['phone'], $fields['address']]);
    somiti_json(['ok' => true, 'id' => (int)$db->lastInsertId(), 'message' => 'Member added successfully.']);
} catch (Throwable $error) {
    somiti_json(['ok' => false, 'message' => 'Database error: ' . $error->getMessage()], 500);
}
