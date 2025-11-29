<?php
session_start();
include 'config.php';

// ✅ Allow only General Manager
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'general manager') {
    die("❌ You do not have permission to view this page.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GM Reports Dashboard</title>
<style>
body { font-family: "Segoe UI", sans-serif; background:#f5f6fa; margin:0; padding:0; }
.container { max-width:1200px; margin:auto; padding:40px; }
h1 { text-align:center; color:#2c3e50; margin-bottom:30px; }
.report-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; }
.report-card { background:#fff; padding:25px 20px; border-radius:10px; text-align:center; box-shadow:0 4px 15px rgba(0,0,0,0.1); transition:0.3s; cursor:pointer; }
.report-card:hover { transform: translateY(-5px); box-shadow:0 6px 20px rgba(0,0,0,0.15); }
.report-card h3 { margin-bottom:15px; color:#e67e22; }
.report-card button { background:#27ae60; color:white; border:none; padding:10px 20px; border-radius:5px; font-weight:bold; cursor:pointer; transition:0.2s; }
.report-card button:hover { background:#219150; }
.back-btn { display:inline-block; margin-bottom:30px; background:#2980b9; color:white; padding:8px 15px; border-radius:5px; text-decoration:none; }
.back-btn:hover { background:#1c6694; }
</style>
</head>
<body>
<div class="container">
    <a class="back-btn" href="ManagerDashboard.php">⬅ Back to Dashboard</a>
    <h1>📊 General Manager Reports</h1>

    <div class="report-grid">
        <!-- Staff Shift Schedule -->
        <div class="report-card">
            <h3>Finance Report</h3>
            <button onclick="window.location.href='financeReports.php'">View Report</button>
        </div>

        <!-- Daily Sales Report -->
        <div class="report-card">
            <h3>Daily Sales</h3>
            <button onclick="window.location.href='daily_cash.php'">View Report</button>
        </div>

        <!-- Inventory / Stock Report -->
        <div class="report-card">
            <h3>Inventory Stock</h3>
            <button onclick="window.location.href='generateStockReports.php'">View Report</button>
        </div>

        <!-- Employee Attendance / Payroll -->
        <div class="report-card">
            <h3>Tax Report</h3>
            <button onclick="window.location.href='generateTaxReports.php'">View Report</button>
        </div>

        <!-- Kitchen / Production Issues -->
        <div class="report-card">
            <h3>Kitchen Report</h3>
            <button onclick="window.location.href='kitchenReports.php'">View Report</button>
        </div>

        <!-- Delivery / Transport Report -->
        <div class="report-card">
            <h3>Delivery / Transport</h3>
            <button onclick="window.location.href='generateTransportReport.php'">View Report</button>
        </div>
    </div>
</div>
</body>
</html>
