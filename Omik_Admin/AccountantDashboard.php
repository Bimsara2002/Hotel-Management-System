<?php
session_start();

// ✅ Allow only Accountant
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'accountant') {
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

<style>
/* ===== General Layout ===== */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f6f8;
    color: #333;
}

header {
    background-color: #343a40;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
}

header h1 {
    margin: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logout-btn {
    background-color: #dc3545;
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    transition: background 0.3s;
}

.logout-btn:hover {
    background-color: #a71d2a;
}

/* ===== Dashboard Layout ===== */
.dashboard-container {
    display: flex;
    min-height: calc(100vh - 100px);
}

.sidebar {
    background-color: #212529;
    color: white;
    width: 250px;
    padding-top: 20px;
    flex-shrink: 0;
}

.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar li {
    padding: 15px 20px;
    cursor: pointer;
    transition: background 0.3s;
}

.sidebar li:hover {
    background-color: #0d6efd;
}

main {
    flex-grow: 1;
    padding: 30px;
    background-color: #fff;
    border-left: 1px solid #ccc;
}

/* ===== Card Buttons ===== */
.cards {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.card {
    flex: 1 1 250px;
    background-color: #3498db;
    color: white;
    padding: 30px 20px;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: transform 0.2s, background 0.3s;
    font-weight: bold;
    font-size: 18px;
}

.card:hover {
    background-color: #2980b9;
    transform: translateY(-5px);
}

/* Footer */
footer {
    text-align: center;
    padding: 15px;
    background-color: #343a40;
    color: white;
}
</style>
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
        <h2>Quick Actions</h2>
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
</div>

<footer>
    <p>&copy; 2025 Omik Family Restaurant Accountant System</p>
</footer>

<script>
// Navigation handling
function openPage(action) {
    const pages = {
        payrolls: 'generatePayrolls.php',
        revenue: 'generateRevenue.php',
        payments: 'supplierPayments.php',
        cash: 'daily_cash.php',
        tax: 'generateTaxReports.php',
        finance: 'financeReports.php',
    };

    if (pages[action]) {
        window.location.href = pages[action];
    } else {
        alert("Unknown action!");
    }
}

// Logout confirmation
function confirmLogout() {
    return confirm("Are you sure you want to logout?");
}
</script>
</body>
</html>
