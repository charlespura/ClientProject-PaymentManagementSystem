<?php
// update.php - Logic for updating complain details
include("connection.php");

if(isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    // Fetch complain details from the database based on the ID
    $sql = "SELECT * FROM ClientTransactions WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        // Bind the ID parameter
        $stmt->bind_param("i", $id);
        // Execute the statement
        $stmt->execute();
        // Store the result
        $result = $stmt->get_result();
        // Check if the result contains any rows
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Output the form to update complain details
            echo "<h2>UPDATE COMPLAIN</h2>";
            echo "<form id='updateForm' method='post' action='update_handler.php'>";
            echo "<label>Client Name:</label> <input type='text' name='client_name' value='" . htmlspecialchars($row["client_name"]) . "'><br>";
            echo "<label>Transaction Date:</label> <input type='date' name='transaction_date' value='" . htmlspecialchars($row["transaction_date"]) . "'><br>";
            echo "<label>Status:</label> <input type='text' name='status' value='" . htmlspecialchars($row["status"]) . "'><br>";
            echo "<label>Payment:</label> <input type='text' name='payment' value='" . htmlspecialchars($row["payment"]) . "'><br>";
            echo "<label>System Name:</label> <input type='text' name='system_name' value='" . htmlspecialchars($row["system_name"]) . "'><br>";
            echo "<label>Date Completed:</label> <input type='date' name='date_completed' value='" . htmlspecialchars($row["date_completed"]) . "'><br>";
            echo "<label>Transaction Cost:</label> <input type='text' name='transaction_cost' value='" . htmlspecialchars($row["transaction_cost"]) . "'><br>";
            echo "<input type='hidden' name='id' value='" . $id . "'>";
            echo "<button type='submit'>Submit</button>";
            echo "</form>";
        } else {
            echo "Complain not found.";
        }
        // Close the statement
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}
?>
