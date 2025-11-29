<?php
session_start();
include 'config.php';

// ✅ Allow only Inventory Manager or HR Manager
if (!isset($_SESSION['staffRole']) || !in_array(strtolower(trim($_SESSION['staffRole'])), ['inventory manager', 'hr manager'])) {
    header("Location: login.html");
    exit;
}

// ===== FILTER INPUT =====
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';
$whereClause = '';
if ($startDate && $endDate) {
    $whereClause = "AND ss.schedule_date BETWEEN '$startDate' AND '$endDate'";
}

// ===== FETCH STAFF REPORT =====
$reportQuery = "
    SELECT s.StaffId, s.FirstName, s.LastName, s.JobRole, s.AccountNumber, s.BasicSalary, s.OtRate, s.Email, s.Status,
           ss.schedule_date, ss.shift_type, ss.notes,
           a.Status AS AttendanceStatus, a.CheckIn, a.CheckOut, a.OThours,
           lr.StartDate AS LeaveStart, lr.EndDate AS LeaveEnd, lr.Reason AS LeaveReason, lr.Status AS LeaveStatus, lr.Type AS LeaveType
    FROM Staff s
    LEFT JOIN StaffSchedule ss ON s.StaffId = ss.staff_id $whereClause
    LEFT JOIN Attendance a ON s.StaffId = a.StaffId AND a.Date = ss.schedule_date
    LEFT JOIN LeaveRequests lr ON s.StaffId = lr.StaffId 
        AND ((lr.StartDate <= ss.schedule_date AND lr.EndDate >= ss.schedule_date) 
        OR (ss.schedule_date IS NULL AND lr.StartDate >= '$startDate' AND lr.EndDate <= '$endDate'))
    ORDER BY s.JobRole, s.FirstName, ss.schedule_date
";

$reportResult = $conn->query($reportQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Report</title>
<style>
body { font-family: "Segoe UI", sans-serif; background:#fdfdfd; margin:30px; }
.container { max-width:1500px; margin:auto; background:white; padding:25px 40px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
h1 { text-align:center; color:#2c3e50; margin-bottom:10px; }
.actions { text-align:center; margin-bottom:20px; }
button, input[type=date] { background:#27ae60; color:white; border:none; padding:8px 15px; border-radius:5px; font-weight:bold; cursor:pointer; }
button:hover { background:#219150; }
.back-btn { background:#2980b9; margin-right:10px; }
.back-btn:hover { background:#1c6694; }
.print-btn { background:#e67e22; }
.print-btn:hover { background:#cf711b; }
table { width:100%; border-collapse:collapse; margin-top:15px; font-size:13px; }
th, td { padding:8px; border:1px solid #ccc; text-align:center; }
th { background:#e67e22; color:#fff; }
tr:nth-child(even) { background:#f9f9f9; }
.section-title { background:#ecf0f1; padding:10px; border-left:5px solid #e67e22; margin-top:20px; color:#2c3e50; font-size:16px; }
@media print {
    button, .actions, .filter-form { display:none; }
    body { margin: 0; }
    table { border:1px solid #000; }
    th { background:#ddd; color:#000; }
}
</style>
<script>
function printReport() {
    window.print();
}
</script>
</head>
<body>
<div class="container">
    <h1>📊 Staff Report</h1>

    <div class="actions">
        <form method="POST" class="filter-form" style="display:inline-block; margin-bottom:15px;">
            <label><strong>From:</strong></label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" required>
            <label><strong>To:</strong></label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" required>
            <button type="submit">🔍 Filter</button>
            <button type="button" onclick="window.location.href='staffReports.php'">Reset</button>
        </form>
        <button class="print-btn" onclick="printReport()">🖨 Print Report</button>
        <button class="back-btn" onclick="window.location.href='hrManagerDashboard.php'">⬅ Back to Dashboard</button>
    </div>

    <?php if ($reportResult->num_rows > 0): ?>
    <table>
        <tr>
            <th>Staff Name</th>
            <th>Job Role</th>
            <th>Status</th>
            <th>Email</th>
            <th>Account #</th>
            <th>Basic Salary</th>
            <th>OT Rate</th>
            <th>Schedule Date</th>
            <th>Shift</th>
            <th>Notes</th>
            <th>Attendance</th>
            <th>CheckIn</th>
            <th>CheckOut</th>
            <th>OT Hours</th>
            <th>Leave</th>
            <th>Leave Type</th>
            <th>Leave Status</th>
        </tr>
        <?php while($row = $reportResult->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) ?></td>
            <td><?= htmlspecialchars($row['JobRole']) ?></td>
            <td><?= htmlspecialchars($row['Status']) ?></td>
            <td><?= htmlspecialchars($row['Email']) ?></td>
            <td><?= htmlspecialchars($row['AccountNumber']) ?></td>
            <td><?= htmlspecialchars($row['BasicSalary']) ?></td>
            <td><?= htmlspecialchars($row['OtRate']) ?></td>
            <td><?= htmlspecialchars($row['schedule_date']) ?></td>
            <td><?= htmlspecialchars($row['shift_type']) ?></td>
            <td><?= htmlspecialchars($row['notes']) ?></td>
            <td><?= htmlspecialchars($row['AttendanceStatus']) ?></td>
            <td><?= htmlspecialchars($row['CheckIn']) ?></td>
            <td><?= htmlspecialchars($row['CheckOut']) ?></td>
            <td><?= htmlspecialchars($row['OThours']) ?></td>
            <td><?= htmlspecialchars($row['LeaveReason']) . ' (' . htmlspecialchars($row['LeaveStart']) . ' to ' . htmlspecialchars($row['LeaveEnd']) . ')' ?></td>
            <td><?= htmlspecialchars($row['LeaveType']) ?></td>
            <td><?= htmlspecialchars($row['LeaveStatus']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p style="text-align:center; color:#777;">No staff records found for selected date range.</p>
    <?php endif; ?>

    <div style="text-align:center; margin-top:20px; font-size:12px; color:#777;">
        Generated on <?= date("F d, Y h:i A") ?>
    </div>
</div>
</body>
</html>
