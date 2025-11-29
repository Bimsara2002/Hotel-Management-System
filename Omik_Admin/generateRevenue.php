<?php
include 'config.php';

$message = "";
$filterQuery = "";

// 1️⃣ Handle revenue generation (Insert / Update)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'generate') {
    $monthYear = $_POST['monthYear'];
    $totalIncome = floatval($_POST['totalIncome']);
    $totalExpenses = floatval($_POST['totalExpenses']);

    if (!empty($monthYear)) {
        $sql = "INSERT INTO Revenue (MonthYear, TotalIncome, TotalExpenses)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                TotalIncome = VALUES(TotalIncome),
                TotalExpenses = VALUES(TotalExpenses),
                GeneratedDate = CURRENT_TIMESTAMP";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdd", $monthYear, $totalIncome, $totalExpenses);
        if ($stmt->execute()) {
            $message = "✅ Revenue generated successfully for $monthYear!";
        } else {
            $message = "❌ Error: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "⚠️ Please select a valid month.";
    }
}

// 2️⃣ Handle filtering
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'filter') {
    $filterMonth = $_POST['filterMonth'] ?? '';
    $fromDate = $_POST['fromDate'] ?? '';
    $toDate = $_POST['toDate'] ?? '';

    if (!empty($filterMonth)) {
        $filterQuery = "WHERE MonthYear = '$filterMonth'";
    } elseif (!empty($fromDate) && !empty($toDate)) {
        $filterQuery = "WHERE GeneratedDate BETWEEN '$fromDate' AND '$toDate'";
    }
}

// 3️⃣ Fetch records (filtered or all)
$query = "SELECT * FROM Revenue $filterQuery ORDER BY GeneratedDate DESC";
$revenues = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Revenue Report</title>
    <link rel="stylesheet" href="generateRevenue.css">
    <script>
        // Auto-calculate Net Revenue
        function calculateNetRevenue() {
            const income = parseFloat(document.getElementById("totalIncome").value) || 0;
            const expenses = parseFloat(document.getElementById("totalExpenses").value) || 0;
            document.getElementById("netRevenue").value = (income - expenses).toFixed(2);
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>📊 Generate & View Revenue Reports</h1>
         <a href="AccountantDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    

        <?php if (!empty($message)): ?>
            <p class="message"><?= $message ?></p>
        <?php endif; ?>

        <!-- 🧮 Generate Revenue Form -->
        <form method="POST" class="revenue-form">
            <input type="hidden" name="action" value="generate">
            <h2>💰 Generate Revenue</h2>

            <label>Select Month:</label>
            <input type="month" name="monthYear" required>

            <label>Total Income (Rs):</label>
            <input type="number" id="totalIncome" name="totalIncome" step="0.01" min="0" oninput="calculateNetRevenue()" required>

            <label>Total Expenses (Rs):</label>
            <input type="number" id="totalExpenses" name="totalExpenses" step="0.01" min="0" oninput="calculateNetRevenue()" required>

            <label>Net Revenue (Rs):</label>
            <input type="text" id="netRevenue" readonly>

            <button type="submit">💾 Generate Revenue</button>
        </form>

        <!-- 🔍 Filter Section -->
        <form method="POST" class="filter-form">
            <input type="hidden" name="action" value="filter">
            <h2>🔍 Filter Revenue Records</h2>

            <div class="filter-row">
                <div>
                    <label>Filter by Month:</label><br>
                    <input type="month" name="filterMonth" style="width: 280px;">
                </div>
                <div>
                    <label>From Date:</label>
                    <input type="date" name="fromDate"style="width: 280px;" >
                </div>
                <div>
                    <label>To Date:</label>
                    <input type="date" name="toDate" style="width: 280px;">
                </div>
            </div>
            <button type="submit">🔎 Apply Filter</button>
        </form>

        <!-- 📅 Table Display -->
        <h2>📅 Generated Revenue Records</h2>
        <table>
            <thead>
                <tr>
                    <th>Month (YYYY-MM)</th>
                    <th>Total Income (Rs)</th>
                    <th>Total Expenses (Rs)</th>
                    <th>Net Revenue (Rs)</th>
                    <th>Generated Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($revenues->num_rows > 0): ?>
                    <?php while ($row = $revenues->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['MonthYear']) ?></td>
                            <td><?= number_format($row['TotalIncome'], 2) ?></td>
                            <td><?= number_format($row['TotalExpenses'], 2) ?></td>
                            <td><?= number_format($row['NetRevenue'], 2) ?></td>
                            <td><?= $row['GeneratedDate'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
