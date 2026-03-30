<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 

// Include database connection
include("connection.php");

// Define variables to store form input and corresponding error messages
$client_name = $transaction_date = $status = $system_name = $transaction_cost = "";
$client_nameErr = $transaction_dateErr = $statusErr = $system_nameErr = $transaction_costErr = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $client_name = trim($_POST["client_name"]);
    $transaction_date = trim($_POST["transaction_date"]);
    $status = trim($_POST["status"]);
    $system_name = trim($_POST["system_name"]);
    $transaction_cost = trim($_POST["transaction_cost"]);

    // Validate input fields
    if (empty($client_name)) {
        $client_nameErr = "Client Name is required";
    }

    if (empty($transaction_date)) {
        $transaction_dateErr = "Transaction Date is required";
    }

    if (empty($status)) {
        $statusErr = "Status is required";
    }

    if (empty($system_name)) {
        $system_nameErr = "System Name is required";
    }

    if (empty($transaction_cost)) {
        $transaction_costErr = "Transaction Cost is required";
    }

    // If no errors, insert data into database
    if (empty($client_nameErr) && empty($transaction_dateErr) && empty($statusErr) && empty($system_nameErr) && empty($transaction_costErr)) {
        // Prepare and bind parameters using a prepared statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO ClientTransactions (client_name, transaction_date, status, system_name, transaction_cost) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $client_name, $transaction_date, $status, $system_name, $transaction_cost);

        // Execute the statement
        if ($stmt->execute()) {
            echo "<script>alert('New Client added successfully'); window.location.href = 'admin.php';</script>";
            exit; // Prevent further execution
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="index.css">
    <title>CP corp</title>
   
    
</head>
<body>
    
<div class="container" style="overflow-y: scroll;">
  <!-- Content that will make the container scroll -->


    <!-- Side Menu -->
    <button onclick="topFunction()" id="myBtn" title="Go to top">Top</button>

    <div class="sidebar">
  <img class="profile-image" src="logo.png" alt="Profile Image">
  <h1>Dashboard</h1>
  <ul>
    <li><a href="#logo">logo</a></li>
    <li><a href="#">Overview</a></li>
    <li><a href="#ID">ID</a></li>
    <li><a href="#Account">Account</a></li>
    <li><a href="#Address">Address</a></li>
    <li><a href="#Email">Email</a></li>
    <li><a href="#Clients">Clients</a></li>
    <li><a href="#Phone Number">Phone Number</a></li>
    <li><a href="#settings">Settings</a></li>
    <li><a href="#help/Support">Help/Support</a></li>
    <li><a href="login.php">Logout</a></li>
    
  </ul>

</div>

<!-- Menu Button -->
<div class="content">


<button class="menu-button" onclick="toggleSidebar()">MENU</button>

    <!-- Main Container -->
    <form class="d-flex" onsubmit="handleSearch(event)">
     <input id="searchInput" class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
    
            </form>
     
        <h4>MY CLIENT</h4>
        <button id="toggleForm">ADD Information</button>

        <div id="tableContainer" style="display: block;">
    <h4 id="text2"> CLIENT INFORMATION</h4>
    <button onclick="toggleRows()"> View Unpaid</button>
    <?php
// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "business";

// Create connection
$connection = new mysqli($host, $username, $password, $database);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Perform a database query
$query = "SELECT * FROM ClientTransactions";
$result = $connection->query($query);

// Check if the query was successful
if ($result) {
    // Initialize total transaction costs
    $totalTransactionCostPaid = 0;
    $totalTransactionCostNotPaid = 0;

    // Display table header
    echo "<table id='myTable'><tr><th>Client no.</th><th>Client Name</th><th>Transaction Date</th><th>Status</th><th>System Name</th><th>Payment</th><th>Date Completed</th><th>Transaction Cost</th><th>Action</th></tr>";

    // Process the results
    if ($result->num_rows > 0) {
        // Loop through the results and display them
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row["id"] . "</td><td>";
            echo isset($row["client_name"]) ? $row["client_name"] : "";
            echo "</td><td>";
            echo isset($row["transaction_date"]) ? $row["transaction_date"] : "";
            echo "</td><td>";
            echo isset($row["status"]) ? $row["status"] : "";
            echo "</td><td>";
            echo isset($row["system_name"]) ? $row["system_name"] : "";
            echo "</td><td>";
            echo isset($row["payment"]) ? $row["payment"] : "";
            echo "</td><td>";
            echo isset($row["date_completed"]) ? $row["date_completed"] : "";
            echo "</td><td>";
            echo isset($row["transaction_cost"]) ? $row["transaction_cost"] : "";
            echo "</td><td>";

            echo "<button onclick='viewinfo(" . $row["id"] . ")'>View</button>" .
                "<button onclick='updateinfo(" . $row["id"] . ")'>Update</button>" .
                "<form method='GET' action='delete.php'>" .
                "<input type='hidden' name='delete_id' value='" . $row['id'] . "'>" .
                "<button   delete-button'>Delete</button>" .
                "</form>" .
                "</div></td></tr>";

            // Add to total transaction cost based on payment status
            if (isset($row["status"])) {
                if ($row["status"] === "paid") {
                    $totalTransactionCostPaid += isset($row["transaction_cost"]) ? $row["transaction_cost"] : 0;
                } else {
                    $totalTransactionCostNotPaid += isset($row["transaction_cost"]) ? $row["transaction_cost"] : 0;
                }
            }
        }

        // Display total transaction costs
        echo "<tr><td colspan='7'><strong>Total Transaction Cost (Paid):</strong></td><td>$totalTransactionCostPaid</td><td></td></tr>";
        echo "<tr><td colspan='7'><strong>Total Transaction Cost (Not Paid):</strong></td><td>$totalTransactionCostNotPaid</td><td></td></tr>";
        echo "<tr><td colspan='7'><strong>Total Transaction Cost (All):</strong></td><td>" . ($totalTransactionCostPaid + $totalTransactionCostNotPaid) . "</td><td></td></tr>";
    } else {
        // Handle case where no results are found
        echo "<tr><td colspan='10'>0 results</td></tr>";
    }
    
    // Close the database connection
    $connection->close();

    // Close table
    echo "</table>";
} else {
    // Handle case where the query fails
    echo "Error: " . $connection->error;
}
?>

    </table>
</div>


  
    </div>
<!-- Form Container -->

<div class="form-container">
<span class="closes"  onclick="closeform()">×</span>
    <!-- FORM -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">



        <div class="inputbox">
            <label for="client_name"><i class='bx bxs-user'></i>Client Name:</label><br>
            <input type="text" id="client_name" name="client_name" placeholder="Client Name" value="<?php echo htmlspecialchars($client_name); ?>"><br>
            <span class="error"><?php echo $client_nameErr; ?></span><br>
        </div>

        <div class="inputbox">
            <label for="transaction_date"><i class='bx bxs-calendar'></i>Transaction Date:</label><br>
            <input type="date" id="transaction_date" name="transaction_date" placeholder="Transaction Date" value="<?php echo htmlspecialchars($transaction_date); ?>"><br>
            <span class="error"><?php echo $transaction_dateErr; ?></span><br>
        </div>

        <div class="inputbox">
            <label for="status"><i class='bx bxs-info-circle'></i>Status:</label><br>
            <select id="status" name="status" onchange="togglePaymentInput()">
                <option value="">Select status</option>
                <option value="paid" <?php if ($status === "paid") echo "selected"; ?>>Paid</option>
                <option value="not_paid" <?php if ($status === "not_paid") echo "selected"; ?>>Not Paid</option>
            </select><br>
            <span class="error"><?php echo $statusErr; ?></span><br>
        </div>

        <div class="inputbox" id="paymentBox">
    <label for="payment"><i class='bx bxs-dollar-circle'></i>Payment:</label><br>
    <select id="payment" name="payment">
        <option value="">Select payment method</option>
        <option value="cash" <?php if (isset($payment) && $payment === "cash") echo "selected"; ?>>Cash</option>
        <option value="online" <?php if (isset($payment) && $payment === "online") echo "selected"; ?>>Online</option>
        <option value="other" <?php if (isset($payment) && $payment === "other") echo "selected"; ?>>Other</option>
    </select><br>
    <input type="text" id="otherPayment" name="other_payment" placeholder="Enter other payment method" style="<?php if (isset($payment) && $payment === "other") echo "display: block;"; else echo "display: none;"; ?>" value="<?php echo isset($payment) ? htmlspecialchars($payment) : ""; ?>"><br>
</div>


        <div class="inputbox">
            <label for="system_name"><i class='bx bxs-laptop'></i>System Name:</label><br>
            <input type="text" id="system_name" name="system_name" placeholder="System Name" value="<?php echo htmlspecialchars($system_name); ?>"><br>
            <span class="error"><?php echo $system_nameErr; ?></span><br>
        </div>

        <div class="inputbox">
            <label for="transaction_cost"><i class='bx bxs-money'></i>Transaction Cost:</label><br>
            <input type="text" id="transaction_cost" name="transaction_cost" placeholder="Transaction Cost" value="<?php echo htmlspecialchars($transaction_cost); ?>"><br>
            <span class="error"><?php echo $transaction_costErr; ?></span><br>
        </div>

        <button type="submit" name="submit" value="Add Transaction">Add Transaction</button>
       
    </form>
</div>


    

<div id="myModal" class="modal" style="display: none;">
    <div class="modal-content" style="margin: 15% auto; width: 50%;">
        <span class="close" onclick="closeModal()">×</span>
        <div id="modalContent"></div>
    </div>
</div>
</div>

<script>
function viewinfo(id) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("modalContent").innerHTML = this.responseText;
            document.getElementById("myModal").style.display = "block"; 
        }
    };
    
    var encodedId = encodeURIComponent(id);
    xhttp.open("GET", "view.php?id=" + encodedId, true);
    xhttp.send();
}


function updateinfo(id) {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("modalContent").innerHTML = this.responseText;
            document.getElementById("myModal").style.display = "block"; 
        }
    };
    xhttp.open("GET", "update.php?id=" + id, true);
    xhttp.send();
}

function closeModal() {
    document.getElementById("myModal").style.display = "none"; 
}
</script>
    <script>
  
        const toggleFormBtn = document.getElementById('toggleForm');
        const formContainer = document.querySelector('.form-container');

        toggleFormBtn.addEventListener('click', () => {
            formContainer.style.display = formContainer.style.display === 'none' ? 'block' : 'none';
        });
    </script>
   
    <script>
function toggleSidebar() {
    document.querySelector('.container').classList.toggle('open-sidebar');
}

</script>
<!-- other  -->
<script>
function togglePaymentInput() {
    var paymentSelect = document.getElementById("payment");
    var otherPaymentInput = document.getElementById("otherPayment");
    if (paymentSelect.value === "other") {
        otherPaymentInput.style.display = "block";
    } else {
        otherPaymentInput.style.display = "none";
    }
}
</script>
<!-- when not paid the payment not show in form pura -->
<script>
    function togglePaymentInput() {
        var status = document.getElementById("status").value;
        var paymentBox = document.getElementById("paymentBox");
        var otherPayment = document.getElementById("otherPayment");

        if (status === "not_paid") {
            paymentBox.style.display = "none";
            otherPayment.style.display = "none";
        } else {
            paymentBox.style.display = "block";
            if (document.getElementById("payment").value === "other") {
                otherPayment.style.display = "block";
            } else {
                otherPayment.style.display = "none";
            }
        }
    }

    
    togglePaymentInput();
</script>
<!-- close form  -->
<script>
    function closeform() {
        var formContainer = document.querySelector(".form-container");
        formContainer.style.display = "none";
    }
</script>
<script>

const searchInput = document.getElementById('searchInput');
const rows = document.querySelectorAll('#myTable tbody tr');


function handleSearch(event) {
  event.preventDefault(); 
  const searchTerm = searchInput.value.trim().toLowerCase(); 
  if (searchTerm === '') { 
    rows.forEach(function(row) {
      row.style.display = 'table-row';
    });
    return;
  }
  rows.forEach(function(row, index) { 
    if (index === 0) return; 
    const cells = row.querySelectorAll('td'); 
    let found = false; 
    cells.forEach(function(cell) { 
      const cellText = cell.textContent.trim().toLowerCase(); 
      if (cellText.includes(searchTerm)) { 
        found = true; 
        return; 
      }
    });
  
    row.style.display = found ? 'table-row' : 'none';
  });
}


searchInput.addEventListener('keyup', function(event) {
  handleSearch(event); 
});
</script>
<!-- paid unpaid -->
<script>
    function toggleRows() {
        var table = document.getElementById("myTable");
        var rows = table.getElementsByTagName("tr");
        
        for (var i = 1; i < rows.length; i++) { // Start from index 1 to skip the header row
            var statusCell = rows[i].getElementsByTagName("td")[3]; // Get the cell containing status
            var status = statusCell.textContent || statusCell.innerText; // Get the status text
            
            if (status === "paid" || status === "un_paid") { // Check if status is "paid" or "un_paid"
                if (rows[i].style.display === "none") {
                    rows[i].style.display = ""; // Show row if it's hidden
                } else {
                    rows[i].style.display = "none"; // Hide row if it's visible
                }
            }
        }
    }
</script>


<script>
// Get the button and the container
let mybutton = document.getElementById("myBtn");
let container = document.querySelector(".container"); // Target the div with class "container"

// When the user scrolls within the container, show the button
container.onscroll = function() {scrollFunction()};

function scrollFunction() {
  if (container.scrollTop > 20) {
    mybutton.style.display = "block";
  } else {
    mybutton.style.display = "none";
  }
}

// When the user clicks on the button, scroll to the top of the container with smooth animation
function topFunction() {
  container.scrollTo({
    top: 0,
    behavior: "smooth"
  });
}
</script>





<button id="myBtn" onclick="topFunction()" style="display: none;">Go to Top</button>


</body>
</html>
