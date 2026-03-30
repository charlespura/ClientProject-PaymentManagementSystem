<?php
include("connection.php");
include("analytics.php");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<p class="text-sm text-slate-500">Invalid request.</p>';
    exit;
}

$id = (int) $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM ClientTransactions WHERE id = ?");

if (!$stmt) {
    echo '<p class="text-sm text-slate-500">Unable to load record.</p>';
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<p class="text-sm text-slate-500">Transaction not found.</p>';
    $stmt->close();
    exit;
}

$row = $result->fetch_assoc();
$status = normalize_status($row["status"] ?? "");
?>
<div class="space-y-6">
    <div class="rounded-[28px] bg-slate-950 p-6 text-white sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-200">Record Details</p>
                <h3 class="mt-3 text-3xl font-semibold tracking-tight"><?php echo htmlspecialchars((string) $row["client_name"]); ?></h3>
                <p class="mt-3 text-sm leading-7 text-slate-300">Full transaction view for this client project and payment record.</p>
            </div>
            <?php if ($status === 'paid'): ?>
                <span class="inline-flex rounded-full border border-emerald-200/20 bg-emerald-400/15 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">Paid</span>
            <?php else: ?>
                <span class="inline-flex rounded-full border border-amber-200/20 bg-amber-400/15 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-200">Not Paid</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
            <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Transaction ID</span>
            <strong class="mt-3 block text-xl font-semibold text-slate-950"><?php echo (int) $row["id"]; ?></strong>
        </div>
        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
            <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Transaction Date</span>
            <strong class="mt-3 block text-xl font-semibold text-slate-950"><?php echo htmlspecialchars((string) $row["transaction_date"]); ?></strong>
        </div>
        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
            <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">System Name</span>
            <strong class="mt-3 block text-xl font-semibold text-slate-950"><?php echo htmlspecialchars((string) $row["system_name"]); ?></strong>
        </div>
        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
            <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Payment</span>
            <strong class="mt-3 block text-xl font-semibold capitalize text-slate-950"><?php echo htmlspecialchars((string) ($row["payment"] !== "" ? $row["payment"] : "Pending")); ?></strong>
        </div>
        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
            <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Date Completed</span>
            <strong class="mt-3 block text-xl font-semibold text-slate-950"><?php echo htmlspecialchars((string) ($row["date_completed"] !== "" ? $row["date_completed"] : "-")); ?></strong>
        </div>
        <div class="rounded-[24px] border border-brand-200 bg-brand-50 p-5">
            <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">Transaction Cost</span>
            <strong class="mt-3 block text-xl font-semibold text-slate-950">₱<?php echo number_format((float) $row["transaction_cost"], 2); ?></strong>
        </div>
    </div>
</div>
<?php
$stmt->close();
?>
