<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
include("connection.php");
include("analytics.php");

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $client_name = sanitize_input($_POST['client_name'] ?? '');
    $transaction_date = sanitize_input($_POST['transaction_date'] ?? '');
    $status = normalize_status($_POST['status'] ?? '');
    $payment = sanitize_input($_POST['payment'] ?? '');
    $other_payment = sanitize_input($_POST['other_payment'] ?? '');
    $system_name = sanitize_input($_POST['system_name'] ?? '');
    $date_completed = !empty($_POST['date_completed']) ? sanitize_input($_POST['date_completed']) : null;
    $transaction_cost = isset($_POST['transaction_cost']) ? (float) $_POST['transaction_cost'] : 0.0;

    if ($payment === 'other' && $other_payment !== '') {
        $payment = $other_payment;
    }

    if ($status === 'not_paid') {
        $payment = '';
        $date_completed = null;
    }

    $sql = "UPDATE ClientTransactions SET client_name=?, transaction_date=?, status=?, payment=?, system_name=?, date_completed=?, transaction_cost=? WHERE id=?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssssdi", $client_name, $transaction_date, $status, $payment, $system_name, $date_completed, $transaction_cost, $id);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: admin.php");
            exit();
        } else {
            echo "Error updating transaction: " . $stmt->error;
        }
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    header("Location: admin.php");
    exit();
}
?>
