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
$payment = trim((string) ($row["payment"] ?? ""));
$paymentOption = in_array($payment, ["cash", "online"], true) ? $payment : ($payment !== "" ? "other" : "");
$otherPayment = $paymentOption === "other" ? $payment : "";
?>
<div class="space-y-6">
    <div class="rounded-[28px] bg-slate-950 p-6 text-white sm:p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-200">Update Record</p>
        <h3 class="mt-3 text-3xl font-semibold tracking-tight">Edit transaction #<?php echo (int) $row["id"]; ?></h3>
        <p class="mt-3 text-sm leading-7 text-slate-300">Update the client project details while keeping the existing database flow unchanged.</p>
    </div>

    <form id="updateForm" method="post" action="update_handler.php" class="space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="update_client_name" class="mb-2 block text-sm font-semibold text-slate-700">Client Name</label>
                <input type="text" id="update_client_name" name="client_name" value="<?php echo htmlspecialchars((string) $row["client_name"]); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
            </div>

            <div>
                <label for="update_transaction_date" class="mb-2 block text-sm font-semibold text-slate-700">Transaction Date</label>
                <input type="date" id="update_transaction_date" name="transaction_date" value="<?php echo htmlspecialchars((string) $row["transaction_date"]); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
            </div>

            <div>
                <label for="update_status" class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                <select id="update_status" name="status" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
                    <option value="paid" <?php echo $status === "paid" ? "selected" : ""; ?>>Paid</option>
                    <option value="not_paid" <?php echo $status === "not_paid" ? "selected" : ""; ?>>Not Paid</option>
                </select>
            </div>

            <div id="update_payment_box" class="sm:col-span-2">
                <label for="update_payment" class="mb-2 block text-sm font-semibold text-slate-700">Payment Method</label>
                <select id="update_payment" name="payment" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
                    <option value="">Select payment method</option>
                    <option value="cash" <?php echo $paymentOption === "cash" ? "selected" : ""; ?>>Cash</option>
                    <option value="online" <?php echo $paymentOption === "online" ? "selected" : ""; ?>>Online</option>
                    <option value="other" <?php echo $paymentOption === "other" ? "selected" : ""; ?>>Other</option>
                </select>
                <input type="text" id="update_other_payment" name="other_payment" placeholder="Enter custom payment method" value="<?php echo htmlspecialchars($otherPayment); ?>" class="mt-3 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
            </div>

            <div class="sm:col-span-2">
                <label for="update_system_name" class="mb-2 block text-sm font-semibold text-slate-700">System Name</label>
                <input type="text" id="update_system_name" name="system_name" value="<?php echo htmlspecialchars((string) $row["system_name"]); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
            </div>

            <div id="update_date_completed_wrap">
                <label for="update_date_completed" class="mb-2 block text-sm font-semibold text-slate-700">Date Completed</label>
                <input type="date" id="update_date_completed" name="date_completed" value="<?php echo htmlspecialchars((string) $row["date_completed"]); ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
            </div>

            <div>
                <label for="update_transaction_cost" class="mb-2 block text-sm font-semibold text-slate-700">Transaction Cost</label>
                <input type="number" step="0.01" id="update_transaction_cost" name="transaction_cost" value="<?php echo htmlspecialchars((string) $row["transaction_cost"]); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
            </div>
        </div>

        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-6">
            <button type="submit" class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Save Changes</button>
        </div>
    </form>
</div>
<?php
$stmt->close();
?>
