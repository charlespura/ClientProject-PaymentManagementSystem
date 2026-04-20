<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("connection.php");
include("analytics.php");

$client_name = "";
$transaction_date = "";
$status = "";
$system_name = "";
$transaction_cost = "";
$payment = "";
$date_completed = "";

$client_nameErr = "";
$transaction_dateErr = "";
$statusErr = "";
$system_nameErr = "";
$transaction_costErr = "";
$successMessage = "";
$flashMessage = "";
$flashTone = "emerald";
$undoId = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $client_name = trim($_POST["client_name"] ?? "");
    $transaction_date = trim($_POST["transaction_date"] ?? "");
    $status = normalize_status($_POST["status"] ?? "");
    $system_name = trim($_POST["system_name"] ?? "");
    $transaction_cost = trim($_POST["transaction_cost"] ?? "");
    $payment = trim($_POST["payment"] ?? "");
    $date_completed = trim($_POST["date_completed"] ?? "");
    $other_payment = trim($_POST["other_payment"] ?? "");

    if ($payment === "other" && $other_payment !== "") {
        $payment = $other_payment;
    }

    if ($client_name === "") {
        $client_nameErr = "Client name is required.";
    }

    if ($transaction_date === "") {
        $transaction_dateErr = "Transaction date is required.";
    }

    if ($status === "") {
        $statusErr = "Status is required.";
    }

    if ($system_name === "") {
        $system_nameErr = "System name is required.";
    }

    if ($transaction_cost === "" || !is_numeric($transaction_cost)) {
        $transaction_costErr = "Enter a valid transaction cost.";
    }

    if ($status === "not_paid") {
        $payment = "";
        $date_completed = "";
    }

    if ($client_nameErr === "" && $transaction_dateErr === "" && $statusErr === "" && $system_nameErr === "" && $transaction_costErr === "") {
        $insertDateCompleted = $date_completed !== "" ? $date_completed : null;
        $stmt = $conn->prepare("INSERT INTO ClientTransactions (client_name, transaction_date, status, system_name, payment, date_completed, transaction_cost) VALUES (?, ?, ?, ?, ?, ?, ?)");

        if ($stmt) {
            $amount = (float) $transaction_cost;
            $stmt->bind_param("ssssssd", $client_name, $transaction_date, $status, $system_name, $payment, $insertDateCompleted, $amount);

            if ($stmt->execute()) {
                header("Location: admin.php?success=1");
                exit;
            }
        }
    }
}

if (isset($_GET["success"])) {
    $successMessage = "Client transaction added successfully.";
}

if (isset($_GET["deleted"])) {
    $flashMessage = "Record moved to trash. You can undo the delete below.";
    $flashTone = "amber";
    $undoId = isset($_GET["undo_id"]) ? (int) $_GET["undo_id"] : 0;
} elseif (isset($_GET["restored"])) {
    $flashMessage = "Deleted record restored successfully.";
    $flashTone = "emerald";
} elseif (isset($_GET["purged"])) {
    $flashMessage = "Record permanently deleted from trash.";
    $flashTone = "rose";
}

$dashboardData = fetch_dashboard_data($conn);
$transactions = $dashboardData["transactions"];
$metrics = $dashboardData["metrics"];
$monthlyRevenue = $dashboardData["monthly_revenue"];
$paymentBreakdown = $dashboardData["payment_breakdown"];
$deletedTransactions = fetch_deleted_transactions($conn);
$highestRevenue = 0;

foreach ($monthlyRevenue as $month) {
    if ($month["value"] > $highestRevenue) {
        $highestRevenue = $month["value"];
    }
}

$topPaymentMethod = (string) (array_key_first($paymentBreakdown) ?? "No data");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="tailwind.css">
    <style>
        body {
            font-family: "Instrument Sans", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(23, 120, 255, 0.18), transparent 28%),
                radial-gradient(circle at top right, rgba(14, 165, 163, 0.16), transparent 32%),
                linear-gradient(180deg, #f7fafc 0%, #eef4ff 42%, #f5f7fb 100%);
        }

        .scrollbar-thin::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.65);
            border-radius: 999px;
        }
    </style>
    <title>Client Project & Payment Management System</title>
</head>
<body class="min-h-screen text-slate-900">
    <div class="relative min-h-screen">
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgba(148,163,184,0.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(148,163,184,0.08)_1px,transparent_1px)] bg-[size:72px_72px] opacity-40"></div>

        <div class="mx-auto flex min-h-screen max-w-[1600px] lg:flex-row">
            <aside id="sidebar" class="scrollbar-thin fixed inset-y-0 left-0 z-40 w-[76vw] max-w-68 -translate-x-full overflow-y-auto border-r border-white/40 bg-slate-950/95 px-5 py-6 text-white shadow-soft backdrop-blur-xl transition-transform duration-300 sm:w-68 sm:px-5 sm:py-7 lg:w-64 lg:max-w-64">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/15">
                        <img class="h-11 w-11 object-contain" src="logo.png" alt="Business logo">
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Client Ops</p>
                        <h1 class="mt-1 text-2xl font-semibold text-white">Payment System</h1>
                    </div>
                </div>

                <div class="mt-8 rounded-3xl border border-white/10 bg-white/5 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-200">Overview</p>
                    <p class="mt-3 text-3xl font-semibold"><?php echo number_format($metrics["total_clients"]); ?></p>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Client records managed inside one professional dashboard.</p>
                </div>

                <nav class="mt-7 space-y-2 text-sm">
                    <a href="#overview" class="block rounded-2xl px-4 py-3 font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">Dashboard</a>
                    <a href="#analytics" class="block rounded-2xl px-4 py-3 font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">Analytics</a>
                    <a href="#insights" class="block rounded-2xl px-4 py-3 font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">Insights</a>
                    <a href="#records" class="block rounded-2xl px-4 py-3 font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">Client Records</a>
                    <a href="#trash" class="block rounded-2xl px-4 py-3 font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">Trash Management</a>
                    <button id="openFormLink" type="button" class="w-full rounded-2xl bg-gradient-to-r from-sky-400 via-brand-500 to-cyan-400 px-4 py-3 text-left font-semibold text-white shadow-lg shadow-sky-950/20 transition hover:scale-[1.01]">Add Client Record</button>
                </nav>

                <div class="mt-8 rounded-3xl border border-emerald-400/20 bg-emerald-500/10 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200">Collected Revenue</p>
                    <p class="mt-3 text-2xl font-semibold text-white">₱<?php echo number_format($metrics["paid_revenue"], 2); ?></p>
                    <p class="mt-2 text-sm text-slate-300"><?php echo number_format($metrics["completion_rate"], 1); ?>% completion rate from all tracked records.</p>
                </div>
            </aside>

            <div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-slate-950/50 backdrop-blur-sm"></div>

            <main class="min-w-0 w-full flex-1 px-4 py-4 sm:px-6 lg:px-8 lg:py-8">
                <div class="overflow-hidden rounded-[32px] border border-white/60 bg-white/70 p-4 shadow-soft backdrop-blur-xl sm:p-6 lg:p-8">
                    <header class="flex flex-col gap-6 border-b border-slate-200/70 pb-8 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-4">
	                            <button id="menuButton" type="button" aria-controls="sidebar" aria-expanded="false" class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm">
	                                <span class="sr-only">Toggle menu</span>
	                                <span id="menuIconOpen" class="text-xl leading-none" aria-hidden="true">☰</span>
	                                <span id="menuIconClose" class="hidden text-3xl leading-none" aria-hidden="true">&times;</span>
	                            </button>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-500">Client Project & Payment Management System</p>
                                <h2 class="mt-3 max-w-3xl text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Professional dashboard for projects, billing, and payment tracking.</h2>
                                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">Monitor revenue, outstanding balances, payment methods, and project progress in one clean Tailwind-based interface while preserving your current PHP workflow.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Top Payment</span>
                                <span class="mt-1 block text-base font-semibold text-slate-900"><?php echo htmlspecialchars($topPaymentMethod); ?></span>
                            </div>
                            <button id="toggleForm" type="button" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/10 transition hover:-translate-y-0.5 hover:bg-brand-700">Add Client Record</button>
                        </div>
                    </header>

                    <?php if ($successMessage !== ""): ?>
                        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                            <?php echo htmlspecialchars($successMessage); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($flashMessage !== ""): ?>
                        <div class="mt-6 flex flex-col gap-3 rounded-2xl border px-5 py-4 text-sm font-medium <?php echo $flashTone === 'amber' ? 'border-amber-200 bg-amber-50 text-amber-800' : ($flashTone === 'rose' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'); ?>">
                            <span><?php echo htmlspecialchars($flashMessage); ?></span>
                            <?php if ($undoId > 0): ?>
                                <form method="POST" action="delete.php" class="flex">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="id" value="<?php echo $undoId; ?>">
                                    <button type="submit" class="inline-flex w-fit rounded-xl border border-amber-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-amber-800 transition hover:bg-amber-100">Undo Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <section id="overview" class="mt-8">
                        <div class="grid gap-4 xl:grid-cols-[1.8fr_1fr]">
                            <div class="overflow-hidden rounded-[30px] bg-slate-950 p-6 text-white shadow-soft sm:p-8">
                                <div class="flex flex-col gap-8 xl:flex-row xl:items-end xl:justify-between">
                                    <div class="max-w-2xl">
                                        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-sky-200">Dashboard</p>
                                        <h3 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">Track projects, payments, and account health in one screen.</h3>
                                        <p class="mt-4 max-w-xl text-sm leading-7 text-slate-300 sm:text-base">All working records remain in the same database flow, with a stronger visual hierarchy, cleaner cards, and a more polished admin experience.</p>
                                    </div>
                                    <div class="grid w-full max-w-full gap-3 md:grid-cols-2 xl:min-w-[360px] xl:max-w-[420px]">
                                        <div class="min-w-0 rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
                                            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Outstanding</p>
                                            <p class="mt-2 break-words text-2xl font-semibold leading-tight lg:text-[1.75rem]">₱<?php echo number_format($metrics["outstanding_revenue"], 2); ?></p>
                                        </div>
                                        <div class="min-w-0 rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
                                            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Average Ticket</p>
                                            <p class="mt-2 break-words text-2xl font-semibold leading-tight lg:text-[1.75rem]">₱<?php echo number_format($metrics["average_ticket"], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[30px] bg-gradient-to-br from-brand-500 via-sky-500 to-cyan-400 p-1 shadow-soft">
                                <div class="h-full rounded-[28px] bg-white/90 p-6 backdrop-blur">
                                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-brand-700">Performance</p>
                                    <h3 class="mt-3 text-2xl font-semibold text-slate-950"><?php echo number_format($metrics["completion_rate"], 1); ?>%</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Completion rate across all client transactions in your database.</p>
                                    <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-cyan-400" style="width: <?php echo max(5, min(100, (float) $metrics["completion_rate"])); ?>%"></div>
                                    </div>
                                    <div class="mt-6 grid gap-3">
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <span class="block text-xs uppercase tracking-[0.24em] text-slate-400">Paid Projects</span>
                                            <span class="mt-1 block text-lg font-semibold text-slate-900"><?php echo number_format($metrics["paid_count"]); ?></span>
                                        </div>
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                            <span class="block text-xs uppercase tracking-[0.24em] text-slate-400">Unpaid Projects</span>
                                            <span class="mt-1 block text-lg font-semibold text-slate-900"><?php echo number_format($metrics["unpaid_count"]); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <article class="rounded-[28px] border border-slate-200/70 bg-white p-6 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Total Clients</p>
                                <p class="mt-4 break-words text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl"><?php echo number_format($metrics["total_clients"]); ?></p>
                                <p class="mt-3 text-sm leading-6 text-slate-500">All client transactions in the system.</p>
                            </article>
                            <article class="rounded-[28px] border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-emerald-700">Paid Projects</p>
                                <p class="mt-4 break-words text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl"><?php echo number_format($metrics["paid_count"]); ?></p>
                                <p class="mt-3 text-sm leading-6 text-emerald-800"><?php echo number_format($metrics["completion_rate"], 1); ?>% completion rate.</p>
                            </article>
                            <article class="rounded-[28px] border border-amber-200 bg-amber-50 p-6 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Outstanding Balance</p>
                                <p class="mt-4 break-words text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">₱<?php echo number_format($metrics["outstanding_revenue"], 2); ?></p>
                                <p class="mt-3 text-sm leading-6 text-amber-800"><?php echo number_format($metrics["unpaid_count"]); ?> records still unpaid.</p>
                            </article>
                            <article class="rounded-[28px] border border-brand-200 bg-brand-50 p-6 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-brand-700">Total Revenue</p>
                                <p class="mt-4 break-words text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">₱<?php echo number_format($metrics["total_revenue"], 2); ?></p>
                                <p class="mt-3 text-sm leading-6 text-brand-800">Average ticket: ₱<?php echo number_format($metrics["average_ticket"], 2); ?></p>
                            </article>
                        </div>
                    </section>

                    <section id="analytics" class="mt-10 grid gap-4 xl:grid-cols-[1.7fr_1fr]">
                        <article class="rounded-[30px] border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">
                            <div class="flex flex-col gap-2 border-b border-slate-100 pb-6">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Revenue Trend</p>
                                <h3 class="text-2xl font-semibold tracking-tight text-slate-950">Monthly transaction value</h3>
                            </div>
                            <div class="mt-8 grid min-h-[320px] grid-cols-2 items-end gap-3 sm:gap-4 sm:grid-cols-3 xl:grid-cols-6">
                                <?php foreach ($monthlyRevenue as $month): ?>
                                    <?php
                                    $barHeight = $highestRevenue > 0 ? max(24, ($month["value"] / $highestRevenue) * 220) : 24;
                                    ?>
                                    <div class="flex flex-col items-center gap-3">
                                        <span class="text-xs font-semibold text-slate-500">₱<?php echo number_format($month["value"], 0); ?></span>
                                        <div class="flex h-60 w-full items-end rounded-[24px] bg-slate-100 p-2">
                                            <div class="w-full rounded-[18px] bg-gradient-to-t from-brand-700 via-brand-500 to-cyan-400 shadow-lg shadow-brand-500/20" style="height: <?php echo (int) round($barHeight); ?>px;"></div>
                                        </div>
                                        <span class="text-center text-xs font-medium text-slate-500"><?php echo htmlspecialchars($month["label"]); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>

                        <article class="rounded-[30px] border border-slate-200/70 bg-slate-950 p-6 text-white shadow-soft sm:p-8">
                            <div class="border-b border-white/10 pb-6">
                                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-200">Analytics</p>
                                <h3 class="mt-2 text-2xl font-semibold tracking-tight">Payment breakdown</h3>
                            </div>

                            <div class="mt-6 space-y-4">
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
                                    <span class="block text-xs uppercase tracking-[0.24em] text-slate-400">Paid Revenue</span>
                                    <strong class="mt-2 block text-2xl font-semibold">₱<?php echo number_format($metrics["paid_revenue"], 2); ?></strong>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
                                    <span class="block text-xs uppercase tracking-[0.24em] text-slate-400">Unpaid Revenue</span>
                                    <strong class="mt-2 block text-2xl font-semibold">₱<?php echo number_format($metrics["outstanding_revenue"], 2); ?></strong>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
                                    <span class="block text-xs uppercase tracking-[0.24em] text-slate-400">Top Payment Method</span>
                                    <strong class="mt-2 block text-2xl font-semibold capitalize"><?php echo htmlspecialchars($topPaymentMethod); ?></strong>
                                </div>
                            </div>

                            <?php if (!empty($paymentBreakdown)): ?>
                                <div class="mt-8 space-y-3">
                                    <?php foreach (array_slice($paymentBreakdown, 0, 4, true) as $method => $count): ?>
                                        <?php $width = $metrics["total_clients"] > 0 ? max(8, ($count / $metrics["total_clients"]) * 100) : 8; ?>
                                        <div>
                                            <div class="mb-2 flex items-center justify-between text-sm">
                                                <span class="capitalize text-slate-300"><?php echo htmlspecialchars((string) $method); ?></span>
                                                <span class="font-semibold text-white"><?php echo (int) $count; ?></span>
                                            </div>
                                            <div class="h-2.5 overflow-hidden rounded-full bg-white/10">
                                                <div class="h-full rounded-full bg-gradient-to-r from-cyan-300 to-brand-400" style="width: <?php echo max(8, min(100, $width)); ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    </section>

                    <section id="insights" class="mt-10">
                        <div class="mb-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">System Insights</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Quick business summary</h3>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-[28px] bg-gradient-to-br from-slate-950 to-slate-800 p-[1px] shadow-sm">
                                <div class="h-full rounded-[27px] bg-slate-950 px-5 py-6 text-slate-100">
                                    <p class="text-sm leading-7"><?php echo number_format($metrics["paid_count"]); ?> paid records are already completed in the system.</p>
                                </div>
                            </div>
                            <div class="rounded-[28px] border border-amber-200 bg-amber-50 px-5 py-6 shadow-sm">
                                <p class="text-sm leading-7 text-amber-900"><?php echo number_format($metrics["unpaid_count"]); ?> records still need payment follow-up.</p>
                            </div>
                            <div class="rounded-[28px] border border-brand-200 bg-brand-50 px-5 py-6 shadow-sm">
                                <p class="text-sm leading-7 text-brand-900">Revenue collected is ₱<?php echo number_format($metrics["paid_revenue"], 2); ?> out of ₱<?php echo number_format($metrics["total_revenue"], 2); ?> total tracked value.</p>
                            </div>
                            <div class="rounded-[28px] border border-emerald-200 bg-emerald-50 px-5 py-6 shadow-sm">
                                <p class="text-sm leading-7 text-emerald-900">Most used payment label: <?php echo htmlspecialchars($topPaymentMethod); ?>.</p>
                            </div>
                        </div>
                    </section>

                    <section id="records" class="mt-10">
                        <div class="rounded-[30px] border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Client Records</p>
                                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Projects and payment list</h3>
                                </div>
                                <div class="w-full lg:w-[360px]">
                                    <label for="searchInput" class="mb-2 block text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Search Records</label>
                                    <input id="searchInput" type="search" placeholder="Search by client, system, status, payment" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none ring-0 transition focus:border-brand-400 focus:bg-white">
                                </div>
                            </div>

                            <div class="scrollbar-thin mt-8 overflow-x-auto">
                                <table id="myTable" class="min-w-full border-separate border-spacing-0 text-left">
                                    <thead>
                                        <tr class="text-xs uppercase tracking-[0.22em] text-slate-400">
                                            <th class="sticky top-0 border-b border-slate-200 bg-white px-4 py-4 font-semibold">ID</th>
                                            <th class="sticky top-0 border-b border-slate-200 bg-white px-4 py-4 font-semibold">Client Name</th>
                                            <th class="sticky top-0 border-b border-slate-200 bg-white px-4 py-4 font-semibold">Transaction Date</th>
                                            <th class="sticky top-0 border-b border-slate-200 bg-white px-4 py-4 font-semibold">Status</th>
                                            <th class="sticky top-0 border-b border-slate-200 bg-white px-4 py-4 font-semibold">System Name</th>
                                            <th class="sticky top-0 border-b border-slate-200 bg-white px-4 py-4 font-semibold">Payment</th>
                                            <th class="sticky top-0 border-b border-slate-200 bg-white px-4 py-4 font-semibold">Date Completed</th>
                                            <th class="sticky top-0 border-b border-slate-200 bg-white px-4 py-4 font-semibold">Transaction Cost</th>
                                            <th class="sticky top-0 border-b border-slate-200 bg-white px-4 py-4 font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php if (count($transactions) > 0): ?>
                                            <?php foreach ($transactions as $row): ?>
                                                <tr class="group text-sm text-slate-600 transition hover:bg-slate-50/80">
                                                    <td class="border-b border-slate-100 px-4 py-4 font-semibold text-slate-900"><?php echo (int) $row["id"]; ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4"><?php echo htmlspecialchars((string) $row["client_name"]); ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4"><?php echo htmlspecialchars((string) $row["transaction_date"]); ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4">
                                                        <?php if ($row["normalized_status"] === "paid"): ?>
                                                            <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Paid</span>
                                                        <?php else: ?>
                                                            <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Not Paid</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="border-b border-slate-100 px-4 py-4"><?php echo htmlspecialchars((string) $row["system_name"]); ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4 capitalize"><?php echo htmlspecialchars((string) ($row["payment"] !== "" ? $row["payment"] : "Pending")); ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4"><?php echo htmlspecialchars((string) ($row["date_completed"] !== "" ? $row["date_completed"] : "-")); ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4 font-semibold text-slate-900">₱<?php echo number_format((float) $row["transaction_cost"], 2); ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4">
                                                        <div class="flex flex-wrap gap-2">
                                                            <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-brand-300 hover:text-brand-700" type="button" onclick="viewInfo(<?php echo (int) $row['id']; ?>)">View</button>
                                                            <button class="rounded-xl bg-slate-950 px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand-700" type="button" onclick="updateInfo(<?php echo (int) $row['id']; ?>)">Update</button>
                                                            <button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100" type="button" onclick="openDeleteModal(<?php echo (int) $row['id']; ?>, '<?php echo htmlspecialchars((string) $row['client_name'], ENT_QUOTES); ?>', 'soft_delete')">Delete</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="px-4 py-16 text-center text-sm text-slate-500">No client records found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section id="trash" class="mt-10">
                        <div class="rounded-[30px] border border-slate-200/70 bg-white p-6 shadow-sm sm:p-8">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Trash Management</p>
                                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Recently deleted records</h3>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Trash Count</span>
                                    <span class="mt-1 block text-base font-semibold text-slate-900"><?php echo number_format(count($deletedTransactions)); ?></span>
                                </div>
                            </div>

                            <div class="scrollbar-thin mt-8 overflow-x-auto">
                                <table class="min-w-full border-separate border-spacing-0 text-left">
                                    <thead>
                                        <tr class="text-xs uppercase tracking-[0.22em] text-slate-400">
                                            <th class="border-b border-slate-200 bg-white px-4 py-4 font-semibold">ID</th>
                                            <th class="border-b border-slate-200 bg-white px-4 py-4 font-semibold">Client Name</th>
                                            <th class="border-b border-slate-200 bg-white px-4 py-4 font-semibold">System Name</th>
                                            <th class="border-b border-slate-200 bg-white px-4 py-4 font-semibold">Deleted At</th>
                                            <th class="border-b border-slate-200 bg-white px-4 py-4 font-semibold">Amount</th>
                                            <th class="border-b border-slate-200 bg-white px-4 py-4 font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($deletedTransactions) > 0): ?>
                                            <?php foreach ($deletedTransactions as $row): ?>
                                                <tr class="text-sm text-slate-600">
                                                    <td class="border-b border-slate-100 px-4 py-4 font-semibold text-slate-900"><?php echo (int) $row["id"]; ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4"><?php echo htmlspecialchars((string) $row["client_name"]); ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4"><?php echo htmlspecialchars((string) $row["system_name"]); ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4"><?php echo htmlspecialchars((string) ($row["deleted_at"] ?? "-")); ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4 font-semibold text-slate-900">₱<?php echo number_format((float) $row["transaction_cost"], 2); ?></td>
                                                    <td class="border-b border-slate-100 px-4 py-4">
                                                        <div class="flex flex-wrap gap-2">
                                                            <form method="POST" action="delete.php">
                                                                <input type="hidden" name="action" value="restore">
                                                                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                                                <button class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100" type="submit">Undo Delete</button>
                                                            </form>
                                                            <button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100" type="button" onclick="openDeleteModal(<?php echo (int) $row['id']; ?>, '<?php echo htmlspecialchars((string) $row['client_name'], ENT_QUOTES); ?>', 'permanent_delete')">Delete Forever</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="px-4 py-16 text-center text-sm text-slate-500">Trash is empty. Deleted records will appear here and can be restored.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <div id="formPanel" class="fixed inset-0 z-50 hidden">
        <div id="formOverlay" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
        <div class="relative ml-auto flex min-h-screen w-full max-w-2xl items-start justify-end">
            <div class="scrollbar-thin h-screen w-full overflow-y-auto border-l border-white/20 bg-white px-5 py-6 shadow-soft sm:px-8 sm:py-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">New Record</p>
                        <h3 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Add Client Record</h3>
                        <p class="mt-3 max-w-lg text-sm leading-7 text-slate-600">Create a new project and payment record without changing the current PHP logic behind your system.</p>
                    </div>
                    <button type="button" id="closeFormButton" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">&times;</button>
                </div>

                <form method="POST" action="admin.php" class="mt-8 space-y-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="client_name" class="mb-2 block text-sm font-semibold text-slate-700">Client Name</label>
                            <input type="text" id="client_name" name="client_name" value="<?php echo htmlspecialchars($client_name); ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
                            <?php if ($client_nameErr !== ""): ?><span class="mt-2 block text-sm text-rose-600"><?php echo htmlspecialchars($client_nameErr); ?></span><?php endif; ?>
                        </div>

                        <div>
                            <label for="transaction_date" class="mb-2 block text-sm font-semibold text-slate-700">Transaction Date</label>
                            <input type="date" id="transaction_date" name="transaction_date" value="<?php echo htmlspecialchars($transaction_date); ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
                            <?php if ($transaction_dateErr !== ""): ?><span class="mt-2 block text-sm text-rose-600"><?php echo htmlspecialchars($transaction_dateErr); ?></span><?php endif; ?>
                        </div>

                        <div>
                            <label for="status" class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                            <select id="status" name="status" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
                                <option value="">Select status</option>
                                <option value="paid" <?php echo $status === "paid" ? "selected" : ""; ?>>Paid</option>
                                <option value="not_paid" <?php echo $status === "not_paid" ? "selected" : ""; ?>>Not Paid</option>
                            </select>
                            <?php if ($statusErr !== ""): ?><span class="mt-2 block text-sm text-rose-600"><?php echo htmlspecialchars($statusErr); ?></span><?php endif; ?>
                        </div>

                        <div id="paymentBox" class="sm:col-span-2">
                            <label for="payment" class="mb-2 block text-sm font-semibold text-slate-700">Payment Method</label>
                            <select id="payment" name="payment" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
                                <option value="">Select payment method</option>
                                <option value="cash" <?php echo $payment === "cash" ? "selected" : ""; ?>>Cash</option>
                                <option value="online" <?php echo $payment === "online" ? "selected" : ""; ?>>Online</option>
                                <option value="other" <?php echo $payment !== "" && !in_array($payment, ["cash", "online"], true) ? "selected" : ""; ?>>Other</option>
                            </select>
                            <input type="text" id="otherPayment" name="other_payment" placeholder="Enter custom payment method" value="<?php echo htmlspecialchars(!in_array($payment, ["cash", "online"], true) ? $payment : ""); ?>" class="mt-3 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="system_name" class="mb-2 block text-sm font-semibold text-slate-700">System Name</label>
                            <input type="text" id="system_name" name="system_name" value="<?php echo htmlspecialchars($system_name); ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
                            <?php if ($system_nameErr !== ""): ?><span class="mt-2 block text-sm text-rose-600"><?php echo htmlspecialchars($system_nameErr); ?></span><?php endif; ?>
                        </div>

                        <div id="dateCompletedWrap">
                            <label for="date_completed" class="mb-2 block text-sm font-semibold text-slate-700">Date Completed</label>
                            <input type="date" id="date_completed" name="date_completed" value="<?php echo htmlspecialchars($date_completed); ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
                        </div>

                        <div>
                            <label for="transaction_cost" class="mb-2 block text-sm font-semibold text-slate-700">Transaction Cost</label>
                            <input type="text" id="transaction_cost" name="transaction_cost" value="<?php echo htmlspecialchars($transaction_cost); ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-brand-400 focus:bg-white">
                            <?php if ($transaction_costErr !== ""): ?><span class="mt-2 block text-sm text-rose-600"><?php echo htmlspecialchars($transaction_costErr); ?></span><?php endif; ?>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-6">
                        <button type="button" id="cancelFormButton" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="myModal" class="fixed inset-0 z-50 hidden">
        <div id="modalOverlay" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
        <div class="relative flex min-h-screen items-center justify-center p-4 sm:p-6">
            <div class="scrollbar-thin relative max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-[30px] border border-white/60 bg-white p-6 shadow-soft sm:p-8">
                <button type="button" id="closeModalButton" class="absolute right-4 top-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">&times;</button>
                <div id="modalContent"></div>
            </div>
        </div>
    </div>

    <div id="deleteConfirmModal" class="fixed inset-0 z-50 hidden">
        <div id="deleteConfirmOverlay" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
        <div class="relative flex min-h-screen items-center justify-center p-4 sm:p-6">
            <div class="w-full max-w-lg rounded-[30px] border border-white/60 bg-white p-6 shadow-soft sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-rose-500">Delete Management</p>
                        <h3 id="deleteModalTitle" class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Delete this record?</h3>
                    </div>
                    <button type="button" id="closeDeleteModalButton" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">&times;</button>
                </div>

                <p id="deleteModalText" class="mt-4 text-sm leading-7 text-slate-600">This action will move the record to trash.</p>

                <form method="POST" action="delete.php" class="mt-6 flex flex-wrap justify-end gap-3">
                    <input type="hidden" name="action" id="deleteActionInput" value="soft_delete">
                    <input type="hidden" name="id" id="deleteIdInput" value="">
                    <button type="button" id="cancelDeleteButton" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                    <button type="submit" id="confirmDeleteButton" class="rounded-2xl bg-rose-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">Move to Trash</button>
                </form>
            </div>
        </div>
    </div>

	    <script>
	    const sidebar = document.getElementById('sidebar');
	    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
	    const menuButton = document.getElementById('menuButton');
	    const menuIconOpen = document.getElementById('menuIconOpen');
	    const menuIconClose = document.getElementById('menuIconClose');
	    const formPanel = document.getElementById('formPanel');
	    const toggleFormBtn = document.getElementById('toggleForm');
	    const openFormLink = document.getElementById('openFormLink');
	    const closeFormButton = document.getElementById('closeFormButton');
	    const cancelFormButton = document.getElementById('cancelFormButton');
    const formOverlay = document.getElementById('formOverlay');
    const statusField = document.getElementById('status');
    const paymentField = document.getElementById('payment');
    const paymentBox = document.getElementById('paymentBox');
    const otherPayment = document.getElementById('otherPayment');
    const dateCompleted = document.getElementById('date_completed');
    const dateCompletedWrap = document.getElementById('dateCompletedWrap');
    const searchInput = document.getElementById('searchInput');
    const modal = document.getElementById('myModal');
    const modalOverlay = document.getElementById('modalOverlay');
    const closeModalButton = document.getElementById('closeModalButton');
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    const deleteConfirmOverlay = document.getElementById('deleteConfirmOverlay');
    const closeDeleteModalButton = document.getElementById('closeDeleteModalButton');
    const cancelDeleteButton = document.getElementById('cancelDeleteButton');
    const deleteActionInput = document.getElementById('deleteActionInput');
    const deleteIdInput = document.getElementById('deleteIdInput');
    const deleteModalTitle = document.getElementById('deleteModalTitle');
	    const deleteModalText = document.getElementById('deleteModalText');
	    const confirmDeleteButton = document.getElementById('confirmDeleteButton');

	    function isSidebarOpen() {
	        return !sidebar.classList.contains('-translate-x-full');
	    }

	    function syncMenuIcon(isOpen) {
	        if (!menuIconOpen || !menuIconClose) {
	            return;
	        }
	        menuIconOpen.classList.toggle('hidden', isOpen);
	        menuIconClose.classList.toggle('hidden', !isOpen);
	        menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
	    }

	    function openSidebar() {
	        sidebar.classList.remove('-translate-x-full');
	        sidebarBackdrop.classList.remove('hidden');
	        syncMenuIcon(true);
	    }

	    function closeSidebar() {
	        sidebar.classList.add('-translate-x-full');
	        sidebarBackdrop.classList.add('hidden');
	        syncMenuIcon(false);
	    }

    function openForm() {
        formPanel.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeForm() {
        formPanel.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function syncPaymentFields() {
        const isPaid = statusField.value === 'paid';

        paymentBox.style.display = isPaid ? 'block' : 'none';
        dateCompletedWrap.style.display = isPaid ? 'block' : 'none';

        if (!isPaid) {
            paymentField.value = '';
            otherPayment.value = '';
            otherPayment.style.display = 'none';
            dateCompleted.value = '';
            return;
        }

        otherPayment.style.display = paymentField.value === 'other' ? 'block' : 'none';
    }

    function bindModalForms() {
        const updateStatus = document.getElementById('update_status');
        const updatePayment = document.getElementById('update_payment');
        const updatePaymentBox = document.getElementById('update_payment_box');
        const updateOtherPayment = document.getElementById('update_other_payment');
        const updateDateCompletedWrap = document.getElementById('update_date_completed_wrap');
        const updateDateCompleted = document.getElementById('update_date_completed');

        if (!updateStatus || !updatePayment || !updatePaymentBox || !updateOtherPayment || !updateDateCompletedWrap) {
            return;
        }

        const syncUpdateForm = function () {
            const isPaid = updateStatus.value === 'paid';
            updatePaymentBox.style.display = isPaid ? 'block' : 'none';
            updateDateCompletedWrap.style.display = isPaid ? 'block' : 'none';

            if (!isPaid) {
                updatePayment.value = '';
                updateOtherPayment.value = '';
                updateOtherPayment.style.display = 'none';
                if (updateDateCompleted) {
                    updateDateCompleted.value = '';
                }
                return;
            }

            updateOtherPayment.style.display = updatePayment.value === 'other' ? 'block' : 'none';
        };

        updateStatus.addEventListener('change', syncUpdateForm);
        updatePayment.addEventListener('change', syncUpdateForm);
        syncUpdateForm();
    }

    function loadModal(url) {
        const request = new XMLHttpRequest();
        request.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                document.getElementById('modalContent').innerHTML = this.responseText;
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                bindModalForms();
            }
        };
        request.open('GET', url, true);
        request.send();
    }

    function viewInfo(id) {
        loadModal('view.php?id=' + encodeURIComponent(id));
    }

    function updateInfo(id) {
        loadModal('update.php?id=' + encodeURIComponent(id));
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function openDeleteModal(id, clientName, action) {
        deleteIdInput.value = id;
        deleteActionInput.value = action;

        if (action === 'permanent_delete') {
            deleteModalTitle.textContent = 'Permanently delete this record?';
            deleteModalText.textContent = 'This will remove "' + clientName + '" from trash permanently. You will not be able to undo this action.';
            confirmDeleteButton.textContent = 'Delete Forever';
            confirmDeleteButton.className = 'rounded-2xl bg-rose-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-rose-800';
        } else {
            deleteModalTitle.textContent = 'Move this record to trash?';
            deleteModalText.textContent = 'This will move "' + clientName + '" to trash. You can undo the delete later from Trash Management.';
            confirmDeleteButton.textContent = 'Move to Trash';
            confirmDeleteButton.className = 'rounded-2xl bg-rose-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-rose-700';
        }

        deleteConfirmModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeDeleteModal() {
        deleteConfirmModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

	    menuButton.addEventListener('click', function () {
	        if (isSidebarOpen()) {
	            closeSidebar();
	            return;
	        }
	        openSidebar();
	    });
	    sidebarBackdrop.addEventListener('click', closeSidebar);
	    toggleFormBtn.addEventListener('click', openForm);
	    openFormLink.addEventListener('click', openForm);
	    closeFormButton.addEventListener('click', closeForm);
	    cancelFormButton.addEventListener('click', closeForm);
    formOverlay.addEventListener('click', closeForm);
    closeModalButton.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', closeModal);
    closeDeleteModalButton.addEventListener('click', closeDeleteModal);
    cancelDeleteButton.addEventListener('click', closeDeleteModal);
    deleteConfirmOverlay.addEventListener('click', closeDeleteModal);

    statusField.addEventListener('change', syncPaymentFields);
    paymentField.addEventListener('change', syncPaymentFields);
    syncPaymentFields();

    searchInput.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        const rows = document.querySelectorAll('#myTable tbody tr');

        rows.forEach(function (row) {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(term) ? '' : 'none';
        });
    });

	    if (<?php echo ($client_nameErr !== "" || $transaction_dateErr !== "" || $statusErr !== "" || $system_nameErr !== "" || $transaction_costErr !== "") ? 'true' : 'false'; ?>) {
	        openForm();
	    }

	    syncMenuIcon(isSidebarOpen());
	    </script>
	</body>
	</html>
