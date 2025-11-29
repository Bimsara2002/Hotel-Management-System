<?php
session_start();
include 'config.php'; // contains $conn

// === Handle Payroll Generation ===
if (isset($_POST['generate_payroll'])) {
    $monthInput = $_POST['month']; // 'YYYY-MM'
    $firstDay = $monthInput . "-01";
    $lastDay = date("Y-m-t", strtotime($firstDay));

    $year = date("Y", strtotime($firstDay));
    $month = date("m", strtotime($firstDay));

    // Fetch all active staff
    $staffQuery = "SELECT StaffId, BasicSalary, OtRate FROM Staff WHERE Status='Active'";
    $staffResult = $conn->query($staffQuery);

    if ($staffResult->num_rows > 0) {
        while ($staff = $staffResult->fetch_assoc()) {
            $staffId = $staff['StaffId'];
            $basicSalary = $staff['BasicSalary'] ?? 0;
            $otRate = $staff['OtRate'] ?? 0;

            //  Check if MonthlyOT record exists
            $checkOT = "SELECT TotalOT FROM MonthlyOT WHERE StaffId=? AND Year=? AND Month=?";
            $stmtOT = $conn->prepare($checkOT);
            $stmtOT->bind_param("iii", $staffId, $year, $month);
            $stmtOT->execute();
            $resultOT = $stmtOT->get_result();

            if ($resultOT->num_rows > 0) {
                $otData = $resultOT->fetch_assoc();
                $totalOT = $otData['TotalOT'];
            } else {
                // Calculate total OT hours from Attendance
                $attQuery = "SELECT SUM(OThours) AS TotalOT FROM Attendance 
                             WHERE StaffId=? AND Date BETWEEN ? AND ?";
                $stmt = $conn->prepare($attQuery);
                $stmt->bind_param("iss", $staffId, $firstDay, $lastDay);
                $stmt->execute();
                $attResult = $stmt->get_result();
                $attData = $attResult->fetch_assoc();
                $totalOT = $attData['TotalOT'] ?? 0;

                // Insert into MonthlyOT
                $insertOT = "INSERT INTO MonthlyOT (StaffId, Year, Month, TotalOT) VALUES (?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE TotalOT=?";
                $stmtInsertOT = $conn->prepare($insertOT);
                $stmtInsertOT->bind_param("iiiii", $staffId, $year, $month, $totalOT, $totalOT);
                $stmtInsertOT->execute();
            }

            $otPay = $totalOT * $otRate;

            // Avoid duplicate payrolls
            $checkPayroll = "SELECT * FROM Payroll WHERE StaffID=? AND Month=?";
            $stmtCheck = $conn->prepare($checkPayroll);
            $stmtCheck->bind_param("is", $staffId, $monthInput);
            $stmtCheck->execute();
            $stmtCheckResult = $stmtCheck->get_result();

            if ($stmtCheckResult->num_rows == 0) {
                $insertPayroll = "INSERT INTO Payroll (StaffID, BaseSalary, OT, PaymentDate, Status, Month) 
                                  VALUES (?, ?, ?, CURRENT_TIMESTAMP, 'Pending', ?)";
                $stmtInsertPayroll = $conn->prepare($insertPayroll);
                $stmtInsertPayroll->bind_param("idds", $staffId, $basicSalary, $otPay, $monthInput);
                $stmtInsertPayroll->execute();
            }
        }
    }

    echo "<script>alert('Payrolls generated for $monthInput successfully!'); window.location.href='generatepayrolls.php';</script>";
    exit;
}

// === Handle Mark as Paid ===
if (isset($_POST['mark_paid'])) {
    $payrollId = intval($_POST['payroll_id']);
    $update = "UPDATE Payroll SET Status='Paid' WHERE PayrollID=?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("i", $payrollId);
    $stmt->execute();
    echo "<script>alert('Payroll marked as Paid.'); window.location.href='generatepayrolls.php';</script>";
    exit;
}

// === Filters ===
$filterMonth = $_GET['month'] ?? '';
$filterStaff = $_GET['staff_name'] ?? '';

$query = "SELECT p.*, s.FirstName, s.LastName 
          FROM Payroll p 
          JOIN Staff s ON p.StaffID = s.StaffID
          WHERE 1";

$params = [];
$types = '';

if ($filterMonth) {
    $query .= " AND p.Month = ?";
    $params[] = $filterMonth;
    $types .= 's';
}

if ($filterStaff) {
    $query .= " AND (s.FirstName LIKE ? OR s.LastName LIKE ?)";
    $params[] = "%$filterStaff%";
    $params[] = "%$filterStaff%";
    $types .= 'ss';
}

$query .= " ORDER BY p.PaymentDate DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$payrolls = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generate Payrolls</title>
<link rel="stylesheet" href="generatePayrolls.css">
<style>
    .container {
    max-width: 1100px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.back-btn {
    background: #555;
    color: #fff;
    padding: 8px 14px;
    text-decoration: none;
    border-radius: 8px;
    transition: 0.2s;
}

.back-btn:hover {
    background: #333;
}

h1, h2 {
    color: #333;
    text-align: center;
}

.payroll-form, .filter-form {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin: 20px 0;
}

.payroll-form input, .filter-form input {
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

button {
    background-color: #007bff;
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

button:hover {
    background-color: #0056b3;
}

.paid-btn {
    background-color: #28a745;
}

.paid-btn:hover {
    background-color: #1e7e34;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

table th, table td {
    padding: 10px;
    border: 1px solid #ddddddff;
    text-align: center;
}

table th {
    background-color: #0c0247ff;
}

</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>💰 Generate Payrolls</h1>
        <a href="AccountantDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    </div>

    <!-- Generate Payroll Form -->
    <form method="POST" class="payroll-form">
        <label for="month">Select Month:</label>
        <input type="month" id="month" name="month" required>
        <button type="submit" name="generate_payroll">Generate Payroll</button>
    </form>

    <!-- Filter Form -->
    <form method="GET" class="filter-form">
        <label for="filter_month">Filter Month:</label>
        <input type="month" id="filter_month" name="month" value="<?= htmlspecialchars($filterMonth) ?>">

        <label for="staff_name">Staff Name:</label>
        <input type="text" id="staff_name" name="staff_name" placeholder="Enter name" value="<?= htmlspecialchars($filterStaff) ?>">

        <button type="submit">Filter</button>
        <a href="generatepayrolls.php"><button type="button">Reset</button></a>
    </form>

    <h2>📋 Generated Payrolls</h2>
    <table>
        <thead>
            <tr>
                <th>Payroll ID</th>
                <th>Staff Name</th>
                <th>Month</th>
                <th>Base Salary</th>
                <th>OT Pay</th>
                <th>Net Pay</th>
                <th>Status</th>
                <th>Payment Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($payrolls->num_rows > 0): ?>
                <?php while ($row = $payrolls->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['PayrollID']) ?></td>
                        <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
                        <td><?= htmlspecialchars($row['Month']) ?></td>
                        <td><?= number_format($row['BaseSalary'], 2) ?></td>
                        <td><?= number_format($row['OT'], 2) ?></td>
                        <td><?= number_format($row['BaseSalary'] + $row['OT'], 2) ?></td>
                        <td><?= htmlspecialchars($row['Status']) ?></td>
                        <td><?= htmlspecialchars($row['PaymentDate']) ?></td>
                        <td>
                            <?php if ($row['Status'] === 'Pending'): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="payroll_id" value="<?= $row['PayrollID'] ?>">
                                    <button type="submit" name="mark_paid" class="paid-btn">Mark as Paid</button>
                                </form>
                            <?php else: ?>
                                ✅ Paid
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9" style="text-align:center;">No payrolls found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.querySelector('.payroll-form').addEventListener('submit', e => {
    const month = document.getElementById('month').value;
    if (!month) {
        alert("Please select a month before generating payroll.");
        e.preventDefault();
    }
});
</script>
</body>
</html>
