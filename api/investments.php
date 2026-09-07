<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

try {
    $db = somiti_db();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'members') {
            $members = $db->query('SELECT id, name, phone, address FROM members WHERE status = 1 ORDER BY name')->fetchAll();
            somiti_json(['ok' => true, 'data' => $members]);
        }

        $stmt = $db->query("SELECT i.id, i.invoice_no, i.issue_date, i.investment_amount, i.profit_amount,
            i.total_payable, i.amount_due, i.status, m.name AS member_name
            FROM investments i INNER JOIN members m ON m.id = i.member_id
            ORDER BY i.id DESC");
        somiti_json(['ok' => true, 'data' => $stmt->fetchAll()]);
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
            somiti_json(['ok' => false, 'message' => 'Invalid investment id.'], 422);
        }
        $stmt = $db->prepare('DELETE FROM investments WHERE id = ?');
        $stmt->execute([$id]);
        somiti_json(['ok' => true, 'message' => 'Investment deleted.']);
    }

    $required = ['invoice_no', 'issue_date', 'member_id', 'investment_amount', 'profit_rate', 'term_months', 'start_date'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            somiti_json(['ok' => false, 'message' => "Missing field: {$field}"], 422);
        }
    }

    $amount = max(0, (float)$input['investment_amount']);
    $rate = max(0, (float)$input['profit_rate']);
    $term = min(60, max(1, (int)$input['term_months']));
    if ($amount <= 0 || $term < 1) {
        somiti_json(['ok' => false, 'message' => 'Enter a valid investment amount and term.'], 422);
    }

    $profit = round($amount * $rate / 100, 2);
    $total = round($amount + $profit, 2);
    $selling = (float)($input['selling_price'] ?? 0);
    $paid = (float)($input['amount_paid'] ?? 0);
    $db->beginTransaction();

    $memberStmt = $db->prepare('UPDATE members SET phone = ?, address = ? WHERE id = ?');
    $memberStmt->execute([
        trim((string)($input['member_phone'] ?? '')) ?: null,
        trim((string)($input['member_address'] ?? '')) ?: null,
        (int)$input['member_id'],
    ]);

    $stmt = $db->prepare('INSERT INTO investments
        (invoice_no, issue_date, member_id, item_description, quantity, unit, selling_price, cost_price, gross_profit,
         investment_amount, amount_paid, amount_due, profit_rate, profit_amount, total_payable, term_months, start_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        trim((string)$input['invoice_no']), $input['issue_date'], (int)$input['member_id'],
        trim((string)($input['item_description'] ?? '')), (float)($input['quantity'] ?? 1),
        trim((string)($input['unit'] ?? 'Piece')), $selling, (float)($input['cost_price'] ?? 0),
        round($selling - (float)($input['cost_price'] ?? 0), 2), $amount, $paid,
        max(0, round($selling - $paid, 2)), $rate, $profit, $total, $term, $input['start_date']
    ]);
    $investmentId = (int)$db->lastInsertId();

    $scheduleStmt = $db->prepare('INSERT INTO investment_schedules
        (investment_id, installment_no, due_date, principal_amount, profit_amount, installment_amount)
        VALUES (?, ?, ?, ?, ?, ?)');
    $installment = floor(($total / $term) * 100) / 100;
    $principalPart = floor(($amount / $term) * 100) / 100;
    $profitPart = floor(($profit / $term) * 100) / 100;
    $start = new DateTimeImmutable($input['start_date']);
    for ($number = 1; $number <= $term; $number++) {
        $isLast = $number === $term;
        $due = $start->modify('+' . ($number - 1) . ' month')->format('Y-m-d');
        $rowPrincipal = $isLast ? round($amount - ($principalPart * ($term - 1)), 2) : $principalPart;
        $rowProfit = $isLast ? round($profit - ($profitPart * ($term - 1)), 2) : $profitPart;
        $rowInstallment = $isLast ? round($total - ($installment * ($term - 1)), 2) : $installment;
        $scheduleStmt->execute([$investmentId, $number, $due, $rowPrincipal, $rowProfit, $rowInstallment]);
    }

    $db->commit();
    somiti_json(['ok' => true, 'id' => $investmentId, 'message' => 'Investment added successfully.']);
} catch (Throwable $error) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    somiti_json(['ok' => false, 'message' => 'Database error: ' . $error->getMessage()], 500);
}