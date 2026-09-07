<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function ledger_type_map(): array
{
    // entry_type => [human label, flow]
    // flow 'in'  -> money moves member -> somiti   (debit  = member's balance grows)
    // flow 'out' -> money moves somiti  -> member  (credit = member's balance shrinks)
    return [
        'collection'      => ['Collection', 'in'],
        'investment'      => ['Investment', 'in'],
        'invest_withdraw' => ['Invest Withdraw', 'out'],
        'payment'         => ['Payment', 'out'],
        'expense'         => ['Expense', 'out'],
    ];
}

function ledger_summary(PDO $db): array
{
    $row = $db->query("SELECT
        COALESCE(SUM(CASE WHEN entry_type IN ('collection','investment') THEN debit ELSE 0 END),0) AS total_in,
        COALESCE(SUM(CASE WHEN entry_type IN ('invest_withdraw','payment','expense') THEN credit ELSE 0 END),0) AS total_out,
        COALESCE(SUM(CASE WHEN entry_type = 'collection' THEN debit ELSE 0 END),0) AS total_collection,
        COALESCE(SUM(CASE WHEN entry_type IN ('investment','invest_withdraw') THEN debit - credit ELSE 0 END),0) AS invest_balance,
        COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) AS net_balance,
        COUNT(*) AS entries
        FROM investor_ledger")->fetch();

    foreach ($row as &$value) {
        if (is_numeric($value)) {
            $value = (float)$value;
        }
    }
    return $row;
}

function ledger_entries(PDO $db, string $memberFilter, string $typeFilter, string $from, string $to): array
{
    $where = [];
    $params = [];

    if ($memberFilter !== 'all') {
        $where[] = 'l.member_id = ?';
        $params[] = (int)$memberFilter;
    }
    if ($typeFilter !== 'all') {
        $where[] = 'l.entry_type = ?';
        $params[] = $typeFilter;
    }
    if ($from !== '') {
        $where[] = 'l.entry_date >= ?';
        $params[] = $from;
    }
    if ($to !== '') {
        $where[] = 'l.entry_date <= ?';
        $params[] = $to;
    }

    $sql = 'SELECT l.*, m.name AS member_name FROM investor_ledger l
            INNER JOIN members m ON m.id = l.member_id';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY l.entry_date DESC, l.id DESC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['debit'] = (float)$row['debit'];
        $row['credit'] = (float)$row['credit'];
        $row['type_label'] = ledger_type_map()[$row['entry_type']][0];
    }
    return $rows;
}

try {
    $db = somiti_db();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        if ($action === 'summary') {
            somiti_json(['ok' => true, 'data' => ledger_summary($db)]);
        }
        if ($action === 'types') {
            somiti_json(['ok' => true, 'data' => ledger_type_map()]);
        }
        if ($action === 'members') {
            $stmt = $db->query('SELECT id, name FROM members WHERE status = 1 ORDER BY name');
            somiti_json(['ok' => true, 'data' => $stmt->fetchAll()]);
        }

        $memberFilter = $_GET['member_id'] ?? 'all';
        $typeFilter = $_GET['type'] ?? 'all';
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';
        somiti_json(['ok' => true, 'data' => ledger_entries($db, $memberFilter, $typeFilter, $from, $to)]);
    }

    if ($method !== 'POST') {
        somiti_json(['ok' => false, 'message' => 'Method not allowed.'], 405);
    }

    $input = $_POST;
    if (!$input) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    }

    if (($input['action'] ?? '') === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id < 1) {
            somiti_json(['ok' => false, 'message' => 'Invalid ledger id.'], 422);
        }
        $db->prepare('DELETE FROM investor_ledger WHERE id = ?')->execute([$id]);
        somiti_json(['ok' => true, 'message' => 'Ledger entry deleted.']);
    }

    $entryType = $input['entry_type'] ?? '';
    $types = ledger_type_map();
    if (!isset($types[$entryType])) {
        somiti_json(['ok' => false, 'message' => 'Invalid entry type.'], 422);
    }

    $memberId = (int)($input['member_id'] ?? 0);
    $amount = (float)($input['amount'] ?? 0);
    if ($memberId < 1) {
        somiti_json(['ok' => false, 'message' => 'Please select a member.'], 422);
    }
    if ($amount <= 0) {
        somiti_json(['ok' => false, 'message' => 'Enter a valid amount greater than zero.'], 422);
    }

    $entryDate = trim((string)($input['entry_date'] ?? ''));
    if ($entryDate === '' || !strtotime($entryDate)) {
        somiti_json(['ok' => false, 'message' => 'Enter a valid date.'], 422);
    }

    $purpose = trim((string)($input['purpose'] ?? ''));
    if ($entryType === 'collection' && $purpose === '') {
        $purpose = 'Monthly fee';
    }
    $method = trim((string)($input['method'] ?? '')) ?: 'Cash';
    $reference = trim((string)($input['reference_no'] ?? '')) ?: null;

    $isIn = $types[$entryType][1] === 'in';
    $ledger = [
        'member_id' => $memberId,
        'entry_type' => $entryType,
        'debit' => $isIn ? $amount : 0,
        'credit' => $isIn ? 0 : $amount,
        'purpose' => $purpose,
        'method' => $method,
        'reference_no' => $reference,
        'entry_date' => $entryDate,
    ];

    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO investor_ledger
            (member_id, entry_type, debit, credit, purpose, method, reference_no, entry_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $ledger['member_id'], $ledger['entry_type'], $ledger['debit'], $ledger['credit'],
            $ledger['purpose'], $ledger['method'], $ledger['reference_no'], $ledger['entry_date'],
        ]);
        $ledgerId = (int)$db->lastInsertId();

        if ($entryType === 'collection') {
            $colStmt = $db->prepare('INSERT INTO member_collections
                (member_id, purpose, method, reference_no, amount, collected_at)
                VALUES (?, ?, ?, ?, ?, ?)');
            $colStmt->execute([
                $memberId, $purpose, $method, $reference, $amount, $entryDate,
            ]);
        }
        $db->commit();
    } catch (Throwable $error) {
        $db->rollBack();
        throw $error;
    }

    somiti_json([
        'ok' => true,
        'id' => $ledgerId,
        'message' => $types[$entryType][0] . ' saved successfully.',
    ]);
} catch (Throwable $error) {
    somiti_json(['ok' => false, 'message' => 'Database error: ' . $error->getMessage()], 500);
}
