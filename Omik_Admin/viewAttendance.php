<?php
include 'config.php';

// Initialize filters
$staffId = $_GET['staffId'] ?? '';
$staffName = $_GET['staffName'] ?? '';
$date = $_GET['date'] ?? '';
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';

// Build query dynamically
$sql = "SELECT a.*, CONCAT(s.FirstName, ' ', s.LastName) AS FullName, s.JobRole 
        FROM Attendance a
        JOIN Staff s ON a.StaffId = s.StaffId
        WHERE 1=1";

if ($staffId !== '') $sql .= " AND a.StaffId = " . intval($staffId);
if ($staffName !== '') $sql .= " AND CONCAT(s.FirstName, ' ', s.LastName) LIKE '%" . mysqli_real_escape_string($conn, $staffName) . "%'";
if ($date !== '') $sql .= " AND a.Date = '" . mysqli_real_escape_string($conn, $date) . "'";
if ($month !== '') $sql .= " AND MONTH(a.Date) = " . intval($month);
if ($year !== '') $sql .= " AND YEAR(a.Date) = " . intval($year);

$sql .= " ORDER BY a.Date DESC";

$result = $conn->query($sql);

// Summary
$summary = [
    'Present' => 0,
    'Absent' => 0,
    'Late' => 0,
    'On Leave' => 0
];

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (isset($summary[$row['Status']])) $summary[$row['Status']]++;
        $data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Viewer</title>
    <link rel="stylesheet" href="viewAttendance.css">
</head>
<body>
<div class="container">
    <h1>Attendance Records</h1>

    <div class="button-group">
        <button class="button-back" onclick="window.location.href='ManagerDashboard.php'">Back to Dashboard</button>
        <button type="button" class="button-reset" onclick="resetFilters()">Reset</button>
    </div>

    <form id="filterForm" method="GET">
        <div class="form-group">
            <label>Staff ID:</label>
            <input type="number" name="staffId" value="<?= htmlspecialchars($staffId) ?>">
        </div>

        <div class="form-group">
            <label>Staff Name:</label>
            <input type="text" name="staffName" value="<?= htmlspecialchars($staffName) ?>">
        </div>

        <div class="form-group">
            <label>Date:</label>
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
        </div>

        <div class="form-group">
            <label>Month:</label>
            <select name="month">
                <option value="">All</option>
                <?php for ($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($month == $m) ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Year:</label>
            <input type="number" name="year" placeholder="2025" value="<?= htmlspecialchars($year) ?>">
        </div>

        <button type="submit" class="button-search">Search</button>
    </form>

    <table>
        <thead>
        <tr>
            <th>Attendance ID</th>
            <th>Staff ID</th>
            <th>Staff Name</th>
            <th>Job Role</th>
            <th>Date</th>
            <th>Status</th>
            <th>Check-In</th>
            <th>Check-Out</th>
            <th>OT Hours</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($data)): ?>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td><?= $row['AttendanceId'] ?></td>
                    <td><?= $row['StaffId'] ?></td>
                    <td><?= htmlspecialchars($row['FullName']) ?></td>
                    <td><?= htmlspecialchars($row['JobRole']) ?></td>
                    <td><?= $row['Date'] ?></td>
                    <td class="status <?= strtolower(str_replace(' ', '-', $row['Status'])) ?>">
                        <?= htmlspecialchars($row['Status']) ?>
                    </td>
                    <td><?= $row['CheckIn'] ?></td>
                    <td><?= $row['CheckOut'] ?></td>
                    <td><?= $row['OThours'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="9" style="text-align:center;">No records found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="summary">
        <h2>Summary</h2>
        <ul>
            <li>Present: <?= $summary['Present'] ?></li>
            <li>Absent: <?= $summary['Absent'] ?></li>
            <li>Late: <?= $summary['Late'] ?></li>
            <li>On Leave: <?= $summary['On Leave'] ?></li>
        </ul>
    </div>
</div>

<script>
// ✅ Fix: Reset button now clears filters and reloads the page
function resetFilters() {
    window.location.href = "<?= basename(__FILE__) ?>";
}
</script>

</body>
</html>
