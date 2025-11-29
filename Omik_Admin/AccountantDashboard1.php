<?php
session_start();

// Check login and role
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'hr manager') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accountant Dashboard</title>
<link rel="stylesheet" href="manager_dashboard.css">
</head>
<body>
<header>
    <h1>Accountant Dashboard</h1>
    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['staffRole']); ?></span>
        <a href="logout.php" class="logout-btn" onclick="return confirmLogout()">Logout</a>
    </div>
</header>

<div class="dashboard-container">
    <nav class="sidebar">
        <ul>
            <li onclick="openPage('payrolls')">💰 Generate Payrolls</li>
            <li onclick="openPage('revenue')">📊 Generate Revenue</li>
            <li onclick="openPage('payments')">💵 Supplier Payments</li>
            <li onclick="openPage('cash')">🧾 Daily Cash Reports</li>
            <li onclick="openPage('tax')">🧮 Generate Tax Reports</li>
            <li onclick="openPage('finance')">📈 Generate Finance Reports</li>

        </ul>
    </nav>

    <main id="content-area">
        <h2>Select an option from the menu</h2>
    </main>
</div>

<footer>
    <p>&copy; 2025 Omik Family Restaurant HR System</p>
</footer>

<script>
function openPage(action) {
    const pages = {
        payrolls: 'addStaff.php',
        revenue: 'trackAttendance.php',
        payments: 'performanceReview.php',
        cash: 'trainingSession.php',
        tax: 'generateSchedule.php',
        finance: 'viewLeaveRequest.php',}
    if (pages[action]) {
        window.location.href = pages[action];
    } else {
        alert("Unknown action!");
    }
}

function confirmLogout() {
    return confirm("Are you sure you want to logout?");
}
</script>
</body>
</html>
