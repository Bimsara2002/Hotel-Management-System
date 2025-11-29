<?php
session_start();

// ✅ Allow only Restaurant Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'restaurant manager') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Restaurant Manager Dashboard</title>
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
</head>

<body>
<header>
    <h1>Restaurant Manager Dashboard</h1>
    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['staffRole']); ?></span>
        <a href="logout.php" class="logout-btn" onclick="return confirmLogout()">Logout</a>
    </div>
</header>

<div class="dashboard-container">
    <nav class="sidebar">
        <ul>
            <li onclick="openPage('addChef')">➕ Add Chef</li>
            <li onclick="openPage('menuManagement')">📦 Create And Update Menu</li>
            <li onclick="openPage('customerOrders')">🔄 View Customer Orders</li>
            <li onclick="openPage('ingredientRequest')">⚠️ Request Ingredients</li>
            <li onclick="openPage('taskDivision')">📋 Dividing Jobs</li>
            <li onclick="openPage('kitchenIssues')">📝 View Kitchen Issues</li>
            <li onclick="openPage('kitchenReports')">📊 Generate Kitchen Reports</li>
            <li onclick="openPage('manageTables')">🍽 Manage Tables</li>
            <li onclick="openPage('AddRoom')"> Add Room</li>
            <li onclick="openPage('roomoffer')"> Manage Room Offer</li>

        </ul>
    </nav>

    <main>
        <h2>Common Activities</h2>
        <div class="card-container">
            <div class="card" onclick="openPage('attendance')">
                <h3>🕒 Add Attendance</h3>
                <p>Record daily attendance</p>
            </div>
            <div class="card" onclick="openPage('leaveRequest')">
                <h3>📝 Request Leave</h3>
                <p>Submit leave requests</p>
            </div>
            <div class="card" onclick="openPage('viewSchedule')">
                <h3>📅 View Schedule</h3>
                <p>Check your shifts</p>
            </div>
        </div>
    </main>
</div>

<footer>
    <p>&copy; 2025 Omik Family Restaurant System</p>
</footer>

<script>
// Navigation handling
function openPage(action) {
    const pages = {
        addChef: 'addChef.php',
        menuManagement: 'createUpdateMenu.php',
        customerOrders: 'viewCustomerOrders.php',
        ingredientRequest: 'requestIngredients.php',
        kitchenIssues: 'viewKitchen_issues.php',
        taskDivision: 'divideJobs.php',
        kitchenReports: 'kitchenReports.php',
        manageTables: 'addTable.php',
        AddRoom: 'addRoom.php',
        roomoffer: 'roomoffer.php',
        // Common activities
        attendance: 'addAttendance.php',
        leaveRequest: 'requestLeave.php',
        viewSchedule: 'viewScheduleall.php'
    };

    if (pages[action]) {
        window.location.href = pages[action];
    } else {
        alert('⚠️ Page not found!');
    }
}

// Logout confirmation
function confirmLogout() {
    return confirm("Are you sure you want to logout?");
}
</script>
</body>
</html>
