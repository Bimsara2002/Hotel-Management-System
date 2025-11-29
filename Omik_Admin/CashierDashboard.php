<?php
session_start();
include 'config.php';

// ✅ Allow only Cashier
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'cashier') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cashier Dashboard</title>
<link rel="stylesheet" href="cashier_style.css">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

</head>
<body>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h2>Omik Cashier</h2>
        </div>
        <ul class="menu">
            <li onclick="openPage('generate_bill')">🧾 Generate Bill</li>
            <li onclick="openPage('walkin_order')">🍴 Walk-in Order</li>
            <li onclick="openPage('view_bills')">🧾 View Bills</li>
            <li onclick="openPage('daily_cash')">💰 Daily Cash</li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <div class="user-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['staffRole']); ?></strong></span>
                <a href="logout.php" class="logout-btn" onclick="return confirmLogout()">Logout</a>
            </div>
        </header>

        <main id="content-area">
        <h2 style="margin-top:5px">Quick Actions</h2>
        <div class="cards">
            <div class="card" onclick="window.location.href='addAttendance.php'">
                📝 Add Attendance
            </div>
            <div class="card" onclick="window.location.href='requestLeave.php'">
                📨 Request Leave
            </div>
            <div class="card" onclick="window.location.href='viewScheduleall.php'">
                📅 View Employee Schedule
            </div>
        </div>
    </main>

        <footer>
            &copy; 2025 Omik Family Restaurant Cashier System
        </footer>
    </div>
</div>

<script>
function openPage(action) {
    const pages = {
        generate_bill: 'select_orders.php',
        walkin_order: 'walkin_order.php',
        view_bills: 'view_bills.php',
        daily_cash: 'daily_cash.php'
    };
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
