<?php
include("connection.php");

if(isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id']; 

    $sql = "SELECT * FROM ClientTransactions WHERE id = '$id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc(); 

        // Changed variable names to match new attributes
        $transaction_id = $row["id"];
        $client_name = $row["client_name"];
        $transaction_date = $row["transaction_date"];
        $status = $row["status"];
        $system_name = $row["system_name"];
        $payment = $row["payment"];
        $date_completed = $row["date_completed"];
        $transaction_cost = $row["transaction_cost"];
     

        // Displaying transaction details with updated variable names
        echo "<h2>Transaction Details</h2>";
        echo "<p><strong>Transaction ID:</strong> $transaction_id</p>";
        echo "<p><strong>Client Name:</strong> $client_name</p>";
        echo "<p><strong>Transaction Date:</strong> $transaction_date</p>";
        echo "<p><strong>Status:</strong> $status</p>";
        echo "<p><strong>System Name:</strong> $system_name</p>";
        echo "<p><strong>Payment:</strong> $payment</p>";
        echo "<p><strong>Date Completed:</strong> $date_completed</p>";
        echo "<p><strong>Transaction Cost:</strong> $transaction_cost</p>";
   
    } else {
        echo "Transaction not found."; 
    }
} else {
    echo "Invalid request."; 
}
?>