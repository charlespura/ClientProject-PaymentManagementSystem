<?php
function normalize_status($status)
{
    $status = strtolower(trim((string) $status));

    if (in_array($status, ["paid", "complete", "completed"], true)) {
        return "paid";
    }

    return "not_paid";
}

function safe_amount($value)
{
    return is_numeric($value) ? (float) $value : 0.0;
}

function ensure_soft_delete_support($conn)
{
    $columnExists = false;
    $result = $conn->query("SHOW COLUMNS FROM ClientTransactions LIKE 'deleted_at'");

    if ($result && $result->num_rows > 0) {
        $columnExists = true;
    }

    if (!$columnExists) {
        $conn->query("ALTER TABLE ClientTransactions ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL");
    }
}

function fetch_dashboard_data($conn)
{
    ensure_soft_delete_support($conn);

    $transactions = [];
    $query = "SELECT id, client_name, transaction_date, status, system_name, payment, date_completed, transaction_cost FROM ClientTransactions WHERE deleted_at IS NULL ORDER BY transaction_date DESC, id DESC";
    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row["normalized_status"] = normalize_status($row["status"] ?? "");
            $row["transaction_cost"] = safe_amount($row["transaction_cost"] ?? 0);
            $transactions[] = $row;
        }
    }

    $totalClients = count($transactions);
    $paidCount = 0;
    $unpaidCount = 0;
    $paidRevenue = 0.0;
    $outstandingRevenue = 0.0;
    $monthlyRevenue = [];
    $paymentBreakdown = [];

    for ($i = 5; $i >= 0; $i--) {
        $monthKey = date("Y-m", strtotime("-{$i} months"));
        $monthlyRevenue[$monthKey] = [
            "label" => date("M Y", strtotime($monthKey . "-01")),
            "value" => 0.0,
        ];
    }

    foreach ($transactions as $row) {
        $amount = $row["transaction_cost"];
        $status = $row["normalized_status"];

        if ($status === "paid") {
            $paidCount++;
            $paidRevenue += $amount;
        } else {
            $unpaidCount++;
            $outstandingRevenue += $amount;
        }

        if (!empty($row["transaction_date"])) {
            $monthKey = date("Y-m", strtotime($row["transaction_date"]));
            if (isset($monthlyRevenue[$monthKey])) {
                $monthlyRevenue[$monthKey]["value"] += $amount;
            }
        }

        $paymentKey = trim((string) ($row["payment"] ?? ""));
        if ($paymentKey === "") {
            $paymentKey = "Pending";
        }

        if (!isset($paymentBreakdown[$paymentKey])) {
            $paymentBreakdown[$paymentKey] = 0;
        }
        $paymentBreakdown[$paymentKey]++;
    }

    arsort($paymentBreakdown);

    $totalRevenue = $paidRevenue + $outstandingRevenue;
    $completionRate = $totalClients > 0 ? round(($paidCount / $totalClients) * 100, 1) : 0;
    $averageTicket = $totalClients > 0 ? $totalRevenue / $totalClients : 0;

    return [
        "transactions" => $transactions,
        "metrics" => [
            "total_clients" => $totalClients,
            "paid_count" => $paidCount,
            "unpaid_count" => $unpaidCount,
            "paid_revenue" => $paidRevenue,
            "outstanding_revenue" => $outstandingRevenue,
            "total_revenue" => $totalRevenue,
            "completion_rate" => $completionRate,
            "average_ticket" => $averageTicket,
        ],
        "monthly_revenue" => array_values($monthlyRevenue),
        "payment_breakdown" => $paymentBreakdown,
    ];
}

function fetch_deleted_transactions($conn)
{
    ensure_soft_delete_support($conn);

    $transactions = [];
    $query = "SELECT id, client_name, transaction_date, status, system_name, payment, date_completed, transaction_cost, deleted_at FROM ClientTransactions WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC, id DESC";
    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row["normalized_status"] = normalize_status($row["status"] ?? "");
            $row["transaction_cost"] = safe_amount($row["transaction_cost"] ?? 0);
            $transactions[] = $row;
        }
    }

    return $transactions;
}
?>
