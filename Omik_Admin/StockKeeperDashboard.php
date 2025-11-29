<?php
session_start();

// ✅ Allow only Stock Keeper
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'stock keeper') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Keeper Dashboard</title>
<link rel="stylesheet" href="manager_dashboard.css">
<style>
/* ===== Quick Actions (common buttons) ===== */
#content-area {
    flex-grow: 1;
    padding: 40px;
    background-color: #ffffff;
    border-left: 1px solid #ddd;
    box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05);
}

#content-area h2 {
    color: #2c3e50;
    font-size: 22px;
    margin-bottom: 10px;
}

#content-area p {
    color: #555;
    font-size: 16px;
    margin-bottom: 25px;
}

.cards {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    margin-top: 20px;
}

.card {
    background-color: #0d6efd;
    color: white;
    font-size: 18px;
    font-weight: 500;
    text-align: center;
    border-radius: 12px;
    padding: 25px 20px;
    flex: 1 1 250px;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    transition: all 0.25s ease-in-out;
    user-select: none;
}

.card:hover {
    background-color: #0b5ed7;
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
}

/* ===== Emoji/Icon Enhancement ===== */
.card::before {
    display: block;
    font-size: 36px;
    margin-bottom: 10px;
}

.card:nth-child(1)::before {
    content: "📝";
}

.card:nth-child(2)::before {
    content: "📨";
}

.card:nth-child(3)::before {
    content: "📅";
}

@media (max-width: 768px) {
    .cards {
        flex-direction: column;
    }

    .card {
        flex: 1 1 100%;
    }
}
</style>
</head>

<body>
<header>
    <h1>Stock Keeper Dashboard</h1>
    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['staffRole']); ?></span>
        <a href="logout.php" class="logout-btn" onclick="return confirmLogout()">Logout</a>
    </div>
</header>

<div class="dashboard-container">
    <nav class="sidebar">
        <ul>
            <li onclick="openPage('addNewItem')">📦 Add New Stock Item</li>
            <li onclick="openPage('updateStock')">🔄 Update Stock Level</li>
            <li onclick="openPage('viewExpired')">⚠️ View Expired Items</li>
            <li onclick="openPage('newItemRequests')">📝 Request New Items</li>
            <li onclick="openPage('departmentRequests')">📋 View Department Requests</li>
            <li onclick="openPage('stockReports')">📊 Generate Stock Reports</li>
        </ul>
    </nav>

    <main id="content-area">
        <h2>Select an option from the menu</h2>
        <p>Use the sidebar to navigate to your stock management tasks.</p>

        <h2>Quick Actions</h2>
        <div class="cards">
            <div class="card" onclick="window.location.href='addAttendance.php'">
                Add Attendance
            </div>
            <div class="card" onclick="window.location.href='requestLeave.php'">
                Request Leave
            </div>
            <div class="card" onclick="window.location.href='viewScheduleall.php'">
                View Employee Schedule
            </div>
        </div>
    </main>
</div>

<footer>
    <p>&copy; 2025 Omik Family Restaurant System</p>
</footer>

<script>
function confirmLogout() {
    return confirm('Are you sure you want to log out?');
}

function openPage(action) {
    const pages = {
        addNewItem: 'addNewItem.php',
        updateStock: 'updateStock.php',
        viewExpired: 'viewExpiredItems.php',
        newItemRequests: 'requestNewItems.php',
        departmentRequests: 'DepartmentRequestsView.php',
        stockReports: 'generateStockReports.php'
    };

    if (pages[action]) {
        window.location.href = pages[action];
    } else {
        alert('⚠️ Page not found!');
    }
}
</script>

</body>
</html>
