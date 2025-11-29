<?php
session_start();

// Check if logged in and role is General Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'general manager') {
    header("Location: login.html");
    exit;
}

include 'config.php';

// Initialize filters
$where = "1=1";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monthYear = $_POST['monthYear'] ?? '';
    $week = $_POST['week'] ?? '';
    $date = $_POST['date'] ?? '';
    $minIncome = $_POST['minIncome'] ?? '';
    $maxIncome = $_POST['maxIncome'] ?? '';
    $minExpense = $_POST['minExpense'] ?? '';
    $maxExpense = $_POST['maxExpense'] ?? '';

    if ($monthYear != '') {
        $where .= " AND MonthYear = '$monthYear'";
    }
    if ($week != '') {
        $where .= " AND WEEK(GeneratedDate,1) = '$week'";
    }
    if ($date != '') {
        $where .= " AND DATE(GeneratedDate) = '$date'";
    }
    if ($minIncome != '') {
        $where .= " AND TotalIncome >= $minIncome";
    }
    if ($maxIncome != '') {
        $where .= " AND TotalIncome <= $maxIncome";
    }
    if ($minExpense != '') {
        $where .= " AND TotalExpenses >= $minExpense";
    }
    if ($maxExpense != '') {
        $where .= " AND TotalExpenses <= $maxExpense";
    }
}

// Fetch filtered revenue
$sql = "SELECT * FROM Revenue WHERE $where ORDER BY MonthYear DESC";
$result = $conn->query($sql);

// Calculate summary
$summarySql = "SELECT COUNT(*) as totalRecords, SUM(TotalIncome) as totalIncome, SUM(TotalExpenses) as totalExpenses, SUM(NetRevenue) as totalNet FROM Revenue WHERE $where";
$summaryResult = $conn->query($summarySql);
$summary = $summaryResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Revenue</title>
<link rel="stylesheet" href="sales.css">
<style>.button-group {
    display: flex;
    justify-content: flex-start;
    gap: 5px;
    margin-bottom: 20px;
}
</style>
<script>
function resetForm() {
    document.getElementById('filterForm').reset();
    window.location.href = 'viewRevenue.php';
}
</script>
</head>
<body>
<div class="container">
    <h1>View Revenue</h1>

    <div class="button-group">
        <button type="button" class="button-back" onclick="window.location.href='ManagerDashboard.php';">Back to Dashboard</button>
    </div>

    <!-- Filter Form -->
    <form method="POST" id="filterForm">
        <!-- Row 1: Date filters -->
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

        <!-- Row 2: Amount filters + buttons -->
        <div class="filter-row">
            <div class="filter-item">
                <label for="minIncome">Min Income:</label>
                <input type="number" name="minIncome" id="minIncome" step="0.01" value="<?php echo $_POST['minIncome'] ?? ''; ?>">
            </div>
            <div class="filter-item">
                <label for="maxIncome">Max Income:</label>
                <input type="number" name="maxIncome" id="maxIncome" step="0.01" value="<?php echo $_POST['maxIncome'] ?? ''; ?>">
            </div>
            <div class="filter-item">
                <label for="minExpense">Min Expense:</label>
                <input type="number" name="minExpense" id="minExpense" step="0.01" value="<?php echo $_POST['minExpense'] ?? ''; ?>">
            </div>
            <div class="filter-item">
                <label for="maxExpense">Max Expense:</label>
                <input type="number" name="maxExpense" id="maxExpense" step="0.01" value="<?php echo $_POST['maxExpense'] ?? ''; ?>">
            </div>
            <div class="filter-item" style="display:flex; align-items:flex-end; gap:10px;">
                <button type="submit">Search</button>
                <button type="button" onclick="resetForm()" class="button-reset" style="background-color:#dc3545">Reset</button>
            </div>
        </div>
    </form>

    <!-- Revenue Table -->
    <table>
        <thead>
            <tr>
                <th>Revenue ID</th>
                <th>Month-Year</th>
                <th>Total Income (Rs.)</th>
                <th>Total Expenses (Rs.)</th>
                <th>Net Revenue (Rs.)</th>
                <th>Generated Date</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['RevenueId']}</td>
                    <td>{$row['MonthYear']}</td>
                    <td>{$row['TotalIncome']}</td>
                    <td>{$row['TotalExpenses']}</td>
                    <td>{$row['NetRevenue']}</td>
                    <td>{$row['GeneratedDate']}</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No revenue records found.</td></tr>";
        }
        ?>
        </tbody>
    </table>

    <!-- Summary Card -->
    <div class="summary">
        <strong>Summary:</strong><br>
        Total Records: <?php echo $summary['totalRecords'] ?? 0; ?><br>
        Total Income: Rs. <?php echo $summary['totalIncome'] ?? 0; ?><br>
        Total Expenses: Rs. <?php echo $summary['totalExpenses'] ?? 0; ?><br>
        Total Net Revenue: Rs. <?php echo $summary['totalNet'] ?? 0; ?>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
