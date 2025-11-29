<?php
session_start();

// ✅ Access Control: Only General Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'general manager') {
    header("Location: login.html");
    exit;
}

include 'config.php';

// =======================
// Initialize Filters
// =======================
$whereOrder = "1=1";
$whereBooking = "1=1";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monthYear = $_POST['monthYear'] ?? '';
    $week = $_POST['week'] ?? '';
    $date = $_POST['date'] ?? '';
    $minAmount = $_POST['minAmount'] ?? '';
    $maxAmount = $_POST['maxAmount'] ?? '';

    if ($monthYear != '') {
        $whereOrder .= " AND DATE_FORMAT(orderDate, '%Y-%m') = '$monthYear'";
        $whereBooking .= " AND DATE_FORMAT(createdAt, '%Y-%m') = '$monthYear'";
    }
    if ($week != '') {
        $whereOrder .= " AND WEEK(orderDate, 1) = '$week'";
        $whereBooking .= " AND WEEK(createdAt, 1) = '$week'";
    }
    if ($date != '') {
        $whereOrder .= " AND DATE(orderDate) = '$date'";
        $whereBooking .= " AND DATE(createdAt) = '$date'";
    }
    if ($minAmount != '') {
        $whereOrder .= " AND amount >= $minAmount";
        $whereBooking .= " AND totalAmount >= $minAmount";
    }
    if ($maxAmount != '') {
        $whereOrder .= " AND amount <= $maxAmount";
        $whereBooking .= " AND totalAmount <= $maxAmount";
    }
}

// =======================
// Fetch Combined Sales Data
// =======================
$sql = "
    SELECT 
        orderId AS id,
        customerId,
        'Food Order' AS category,
        amount AS total,
        orderDate AS saleDate,
        paymentStatus
    FROM customerOrders
    WHERE $whereOrder

    UNION ALL

    SELECT 
        bookingId AS id,
        customerId,
        'Room Booking' AS category,
        totalAmount AS total,
        createdAt AS saleDate,
        paymentStatus
    FROM room_booking
    WHERE $whereBooking

    ORDER BY saleDate DESC
";

$result = $conn->query($sql);

// =======================
// Summary
// =======================
$summarySql = "
    SELECT SUM(total) AS totalAmount, COUNT(*) AS totalSales FROM (
        SELECT amount AS total FROM customerOrders WHERE $whereOrder
        UNION ALL
        SELECT totalAmount AS total FROM room_booking WHERE $whereBooking
    ) AS combined
";

$summaryResult = $conn->query($summarySql);
$summary = $summaryResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finance Summary Report</title>
<link rel="stylesheet" href="sales.css">
<style>
body {
    background: #f9f9f9;
    font-family: 'Poppins', sans-serif;
}
.container {
    width: 90%;
    margin: 30px auto;
    background: #fff;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    color: #333;
}
.filter-row {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 15px;
}
.filter-item {
    flex: 1;
}
input, button {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 6px;
}
button {
    background: #4b6cb7;
    color: white;
    cursor: pointer;
    transition: 0.3s;
}
button:hover {
    background: #3b5998;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}
th, td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}
th {
    background: #4b6cb7;
    color: white;
}
.summary {
    margin-top: 20px;
    background: #f1f1f1;
    padding: 15px;
    border-radius: 8px;
}
.button-group {
    margin-bottom: 15px;
}
.button-back {
    background: #777;
    color: white;
    padding: 8px 14px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.button-back:hover {
    background: #555;
}
</style>
</head>

<body>
<div class="container">
    <h1>Finance Summary Report</h1>
    <div class="button-group">
        <button class="button-back" onclick="history.back()">⬅ Back</button>
    </div>

    <!-- Filter Form -->
    <form method="POST" id="filterForm">
        <div class="filter-row">
            <div class="filter-item">
                <label for="monthYear">Month & Year:</label>
                <input type="month" name="monthYear" id="monthYear" value="<?php echo $_POST['monthYear'] ?? ''; ?>">
            </div>
            <div class="filter-item">
                <label for="week">Week (1-53):</label>
                <input type="number" name="week" id="week" min="1" max="53" value="<?php echo $_POST['week'] ?? ''; ?>">
            </div>
            <div class="filter-item">
                <label for="date">Specific Date:</label>
                <input type="date" name="date" id="date" value="<?php echo $_POST['date'] ?? ''; ?>">
            </div>
        </div>

        <div class="filter-row">
            <div class="filter-item">
                <label for="minAmount">Min Amount:</label>
                <input type="number" name="minAmount" id="minAmount" step="0.01" value="<?php echo $_POST['minAmount'] ?? ''; ?>">
            </div>
            <div class="filter-item">
                <label for="maxAmount">Max Amount:</label>
                <input type="number" name="maxAmount" id="maxAmount" step="0.01" value="<?php echo $_POST['maxAmount'] ?? ''; ?>">
            </div>
            <div class="filter-item" style="display:grid; align-items:end;">
                <button type="submit">Search</button>
                <button type="button" style="margin-top:5px;background:#dc3545;" onclick="resetForm()">Reset</button>
            </div>
        </div>
    </form>

    <!-- Combined Report Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer ID</th>
                <th>Category</th>
                <th>Total (Rs.)</th>
                <th>Sale Date</th>
                <th>Payment Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['customerId']}</td>
                    <td>{$row['category']}</td>
                    <td>{$row['total']}</td>
                    <td>{$row['saleDate']}</td>
                    <td>{$row['paymentStatus']}</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No records found.</td></tr>";
        }
        ?>
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary">
        <h3>Summary</h3>
        <p><strong>Total Sales:</strong> <?php echo $summary['totalSales'] ?? 0; ?></p>
        <p><strong>Total Amount:</strong> Rs. <?php echo number_format($summary['totalAmount'] ?? 0, 2); ?></p>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('filterForm').reset();
    window.location.href = 'FinanceReport.php';
}
</script>

</body>
</html>

<?php $conn->close(); ?>
