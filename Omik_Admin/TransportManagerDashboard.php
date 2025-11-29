<?php
session_start();

// ✅ Allow only Transport Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'transport manager') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transport Manager Dashboard</title>
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

/* ===== Cards for Common Activities ===== */
.card-container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.card {
    flex: 1 1 200px;
    background: #1abc9c;
    color: #fff;
    border-radius: 8px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s, background 0.3s;
}

.card:hover {
    background: #16a085;
    transform: translateY(-5px);
}

.card h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
}

/* Footer */
footer {
    text-align: center;
    padding: 15px;
    background-color: #343a40;
    color: white;
    margin-top: auto;
}
</style>
<!-- ✅ Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
<header>
    <h1><i class="fa-solid fa-truck"></i> Transport Manager Dashboard</h1>
    <div class="user-info">
        <span>👋 Welcome, <?php echo htmlspecialchars($_SESSION['staffRole']); ?></span>
        <a href="logout.php" class="logout-btn" onclick="return confirmLogout()">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </div>
</header>

<div class="dashboard-container">
    <nav class="sidebar">
        <ul>
            <li onclick="openPage('generateReport')">
                <i class="fa-solid fa-chart-line"></i> Generate Transport Report
            </li>
            <li onclick="openPage('addDriver')">
                <i class="fa-solid fa-user-plus"></i> Add Driver  
            </li>
            <li onclick="openPage('addVehicle')">
                <i class="fa-solid fa-car"></i> Add Vehicle  
            </li>
            <li onclick="openPage('viewOrders')">
                <i class="fa-solid fa-box"></i> View Delivery Orders
            </li>
            <li onclick="openPage('deliveryStatus')">
                <i class="fa-solid fa-truck-fast"></i> View Delivery Status
            </li>
            <li onclick="openPage('vehicleIssues')">
                <i class="fa-solid fa-triangle-exclamation"></i> View Vehicle Issues & Maintenance
            </li>
            <li onclick="openPage('TransportExpenses')">
                <i class="fa-solid fa-triangle-exclamation"></i>         
                Manage Transport Expenses
            </li>
        </ul>
    </nav>

    <main>
        <h2>Welcome to the Transport Management Dashboard 🚚</h2>
        <p>Use the sidebar to manage all transportation operations — add drivers, register vehicles, track deliveries, and monitor vehicle issues.</p>
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
    <p>&copy; 2025 Omik Family Restaurant | Transport Manager Dashboard</p>
</footer>

<script>
function openPage(action) {
    const pages = {
        addDriver: 'addDriver.php',
        addVehicle: 'addVehicle.php',
        generateReport: 'generateTransportReport.php',
        viewOrders: 'viewDeliveryOrders.php',
        deliveryStatus: 'viewDeliveryStatus.php',
        vehicleIssues: 'viewVehicleIssues.php',
        TransportExpenses: 'manageTransportExpenses.php'
    };

    if (pages[action]) {
        window.location.href = pages[action];
    } else {
        alert('⚠️ Page not found!');
    }
}

function confirmLogout() {
    return confirm("Are you sure you want to logout?");
}
</script>
</body>
</html>
