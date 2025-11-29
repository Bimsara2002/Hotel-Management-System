<?php
$section = $_GET['section'] ?? '';

switch ($section) {
    case 'schedule':
        echo "<h3>Employee Schedule</h3><p>Display all staff schedules here.</p>";
        break;

    case 'reports':
        echo "<h3>Reports</h3><p>Show weekly and monthly performance reports.</p>";
        break;

    case 'sales':
        echo "<h3>Sales Overview</h3><p>Today's total sales: Rs. 85,000</p>";
        break;

    case 'revenue':
        echo "<h3>Revenue Chart</h3><p>Generate revenue graphs using Chart.js here.</p>";
        break;

    case 'feedback':
        echo "<h3>Customer Feedback</h3><p>View latest customer reviews here.</p>";
        break;

    case 'leave':
        echo "<h3>Manager Leave Requests</h3>
              <p>Pending Leave Requests:</p>
              <button onclick='acceptLeave()'>Accept Request</button>
              <script>
                function acceptLeave(){
                    alert('Leave request accepted.');
                }
              </script>";
        break;

    case 'attendance':
        echo "<h3>Staff Attendance</h3><p>View attendance records of staff.</p>";
        break;

    case 'summary':
        echo "<h3>Summary Report</h3><p>Generate and download summary report as PDF.</p>";
        break;

    case 'payments':
        echo "<h3>Supplier Payments</h3><p>Approve or reject pending supplier payments.</p>";
        break;

    default:
        echo "<p>Invalid section</p>";
}
?>
