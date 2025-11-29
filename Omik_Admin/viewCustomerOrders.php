<?php
include 'config.php';

// ================= Update Order Group Status =================
if(isset($_POST['update_status'])){
    $orderGroup = $_POST['orderGroup'];
    $newStatus = $_POST['status'];

    $stmt = $conn->prepare("UPDATE customerOrders SET status=? WHERE orderGroup=?");
    $stmt->bind_param("ss", $newStatus, $orderGroup);
    $stmt->execute();

    echo json_encode(['success'=>true]);
    exit;
}

// ================= Fetch Orders (for AJAX) =================
if(isset($_GET['fetch_orders'])){
    $filterStatus = isset($_GET['filterStatus']) ? $_GET['filterStatus'] : '';

    $sql = "SELECT co.orderGroup,
                   GROUP_CONCAT(CONCAT(f.foodName, ' x', co.quantity) SEPARATOR ', ') AS items,
                   GROUP_CONCAT(co.status) AS statuses,
                   MAX(co.orderDate) AS orderDate,
                   co.paymentType,
                   co.paymentStatus,
                   co.type,
                   co.deliveryStatus
            FROM customerOrders co
            LEFT JOIN food f ON co.foodId = f.foodId";

    if($filterStatus){
        $sql .= " WHERE co.status = '". $conn->real_escape_string($filterStatus) ."'";
    }

    $sql .= " GROUP BY co.orderGroup, co.paymentType, co.paymentStatus, co.type, co.deliveryStatus
              ORDER BY orderDate DESC";

    $result = $conn->query($sql);
    $rows = [];
    while($row = $result->fetch_assoc()){
        $rows[] = $row;
    }

    echo json_encode($rows);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Orders - Omik Restaurant</title>
<link rel="stylesheet" href="viewCustomerOrders.css">
</head>
<body>

<div class="container">
    <h1>📋 Customer Orders</h1>
    <a href="RestaurantManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>

    <div class="filter-container">
        <label for="filterStatus">Filter Status:</label>
        <select id="filterStatus">
            <option value="">All</option>
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
        </select>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order Group</th>
                <th>Items</th>
                <th>Order Date</th>
                <th>Payment Type</th>
                <th>Payment Status</th>
                <th>Type</th>
                <th>Delivery Status</th>
                <th>Status</th>
                <th>Update</th>
            </tr>
        </thead>
        <tbody id="ordersTableBody">
            <!-- Orders will be loaded here -->
        </tbody>
    </table>
</div>

<script>
// ===== Status color classes =====
function setStatusColor(select){
    const val = select.value.toLowerCase().replace(' ', '');
    select.classList.remove('status-pending','status-completed','status-cancelled','status-inprogress');
    if(val==='pending') select.classList.add('status-pending');
    else if(val==='inprogress') select.classList.add('status-inprogress');
    else if(val==='completed') select.classList.add('status-completed');
    else if(val==='cancelled') select.classList.add('status-cancelled');
}

// ===== Fetch orders =====
function fetchOrders(filterStatus=''){
    fetch(`?fetch_orders=1&filterStatus=${encodeURIComponent(filterStatus)}`)
    .then(res => res.json())
    .then(orders => {
        const tbody = document.getElementById('ordersTableBody');
        tbody.innerHTML = '';
        if(orders.length === 0){
            tbody.innerHTML = '<tr><td colspan="9">No orders found.</td></tr>';
            return;
        }

        orders.forEach(order => {
            const tr = document.createElement('tr');

            // Determine group status
            const statuses = order.statuses.split(',');
            let groupStatus = 'Pending';
            if(statuses.every(s => s === 'Completed')) groupStatus = 'Completed';
            else if(statuses.includes('Cancelled')) groupStatus = 'Cancelled';
            else if(statuses.includes('In Progress')) groupStatus = 'In Progress';

            tr.innerHTML = `
                <td>${order.orderGroup}</td>
                <td>${order.items}</td>
                <td>${order.orderDate}</td>
                <td>${order.paymentType ?? 'N/A'}</td>
                <td>${order.paymentStatus ?? 'N/A'}</td>
                <td>${order.type}</td>
                <td>${order.deliveryStatus}</td>
                <td>
                    <select class="statusSelect" data-group="${order.orderGroup}">
                        <option value="Pending" ${groupStatus==='Pending'?'selected':''}>Pending</option>
                        <option value="In Progress" ${groupStatus==='In Progress'?'selected':''}>In Progress</option>
                        <option value="Completed" ${groupStatus==='Completed'?'selected':''}>Completed</option>
                        <option value="Cancelled" ${groupStatus==='Cancelled'?'selected':''}>Cancelled</option>
                    </select>
                </td>
                <td><button class="updateBtn" onclick="updateStatus('${order.orderGroup}')">Update</button></td>
            `;
            tbody.appendChild(tr);

            const select = tr.querySelector('.statusSelect');
            setStatusColor(select);
            select.addEventListener('change', () => setStatusColor(select));
        });
    });
}

// ===== Update order group status =====
function updateStatus(orderGroup){
    const select = document.querySelector(`.statusSelect[data-group="${orderGroup}"]`);
    const formData = new FormData();
    formData.append('update_status', true);
    formData.append('orderGroup', orderGroup);
    formData.append('status', select.value);

    fetch('', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert('✅ Order status updated for the group!');
            fetchOrders(); // refresh table
        } else alert('❌ Failed to update status!');
    });
}

// ===== Filter change =====
document.getElementById('filterStatus').addEventListener('change', function(){
    fetchOrders(this.value);
});

// ===== Initial fetch =====
fetchOrders();
</script>
</body>
</html>
