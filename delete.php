<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("connection.php");

function deleteRecord($id, $conn) {
    $stmt = $conn->prepare("DELETE FROM ClientTransactions WHERE id = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();

    return $success;
}

if (isset($_GET["delete_id"])) {
    $delete_id = (int) $_GET["delete_id"];
    echo "<script>
        var result = confirm('Are you sure you want to delete this record?');
        if (result) {
            window.location.href = 'delete.php?confirmed_delete_id={$delete_id}';
        } else {
            window.location.href = 'admin.php';
        }
    </script>";
    exit;
}

if (isset($_GET["confirmed_delete_id"])) {
    $confirmed_delete_id = (int) $_GET["confirmed_delete_id"];

    if (deleteRecord($confirmed_delete_id, $conn)) {
        header("Location: admin.php");
        exit;
    }

    echo "Error deleting record: " . $conn->error;
}

$conn->close();
?>
