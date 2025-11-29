<?php
session_start();

// ✅ Allow only Chef
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'chef') {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chef Dashboard</title>
<link rel="stylesheet" href="manager_dashboard.css">
<!-- ✅ Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    /* ===== Main Content Area ===== */
/* ===== Main Content Area ===== */
main {
    flex-grow: 1;
    padding: 50px;
    background-color: #fdfdfd;
    border-left: 1px solid #ddd;
    box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.05);
    min-height: calc(100vh - 100px);
    overflow-y: auto;
}

main h2 {
    color: #2c3e50;
    font-size: 24px;
    margin-bottom: 15px;
    border-left: 5px solid #0d6efd;
    padding-left: 10px;
}

main p {
    color: #555;
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 25px;
}

/* ===== Quick Action Cards ===== */
.cards {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-top: 25px;
    justify-content: flex-start;
}

.card {
    background: linear-gradient(135deg, #0d6efd, #007bff);
    color: white;
    font-size: 18px;
    font-weight: 600;
    text-align: center;
    border-radius: 15px;
    padding: 30px 25px;
    flex: 1 1 280px;
    max-width: 320px;
    cursor: pointer;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    transition: all 0.25s ease-in-out;
    user-select: none;
}

.card:hover {
    background: linear-gradient(135deg, #0b5ed7, #0056b3);
    transform: translateY(-6px) scale(1.03);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
}

/* ===== Icon Styling for Cards ===== */
.card::before {
    display: block;
    font-size: 40px;
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

/* ===== Responsive Layout ===== */
@media (max-width: 900px) {
    .cards {
        justify-content: center;
    }

    .card {
        flex: 1 1 100%;
        max-width: none;
    }

    main {
        padding: 25px;
    }
}

</style>
</head>

<body>
<header>
    <h1><i class="fa-solid fa-utensils"></i> Chef Dashboard</h1>
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
            <li onclick="openPage('viewOrders')">
                <i class="fa-solid fa-receipt"></i> View Orders
            </li>
            <li onclick="openPage('requestIngredients')">
                <i class="fa-solid fa-basket-shopping"></i> Request Ingredients
            </li>
            <li onclick="openPage('reportIssues')">
                <i class="fa-solid fa-triangle-exclamation"></i> Report Kitchen Issues
            </li>
            <li onclick="openPage('addRecipe')">
                <i class="fa-solid fa-book-open"></i> Add New Recipes
            </li>
        </ul>
    </nav>

    <main>
        <h2>Welcome to the Kitchen Management Dashboard 🍳</h2>
        <p>Use the sidebar to manage your daily kitchen operations — track orders, request ingredients, report issues, or add new recipes.</p>
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
    <p>&copy; 2025 Omik Family Restaurant | Chef Dashboard</p>
</footer>

<script>
function openPage(action) {
    const pages = {
        viewOrders: 'viewJobOrders.php',
        requestIngredients: 'chefrequestIngredients.php',
        reportIssues: 'reportKitchenIssues.php',
        addRecipe: 'addRecipe.php'
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
