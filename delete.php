<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("connection.php");
include("analytics.php");

ensure_soft_delete_support($conn);

function redirect_with_status($query)
{
    header("Location: admin.php?" . $query);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect_with_status("");
}

$action = $_POST["action"] ?? "";
$id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;

if ($id <= 0) {
    redirect_with_status("");
}

if ($action === "soft_delete") {
    $stmt = $conn->prepare("UPDATE ClientTransactions SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    redirect_with_status("deleted=1&undo_id=" . $id . "#trash");
}

if ($action === "restore") {
    $stmt = $conn->prepare("UPDATE ClientTransactions SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    redirect_with_status("restored=1#trash");
}

if ($action === "permanent_delete") {
    $stmt = $conn->prepare("DELETE FROM ClientTransactions WHERE id = ? AND deleted_at IS NOT NULL");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    redirect_with_status("purged=1#trash");
}

redirect_with_status("");
?>
