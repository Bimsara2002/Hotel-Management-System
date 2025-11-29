<?php
include 'config.php';

// ================= Handle job assignment =================
if (isset($_POST['assign_job'])) {
    $orderGroup = $_POST['order_group'];
    $chef_id = intval($_POST['chef_id']);

    // Check if this chef is already assigned to this group
    $check = $conn->prepare("SELECT * FROM KitchenJob WHERE orderGroup=? AND chef_id=?");
    $check->bind_param("si", $orderGroup, $chef_id);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows === 0) {
        // Insert into KitchenJob
        $stmt = $conn->prepare("INSERT INTO KitchenJob (orderGroup, chef_id, status) VALUES (?, ?, 'Assigned')");
        $stmt->bind_param("si", $orderGroup, $chef_id);
        $stmt->execute();

        // Update Chef status → Busy
        $updateChef = $conn->prepare("UPDATE Chef SET status='Busy' WHERE chef_id=?");
        $updateChef->bind_param("i", $chef_id);
        $updateChef->execute();

        // Update all orders in this group → In Progress
        $updateOrders = $conn->prepare("UPDATE customerOrders SET status='In Progress' WHERE orderGroup=?");
        $updateOrders->bind_param("s", $orderGroup);
        $updateOrders->execute();

        $successMsg = "✅ Chef assigned successfully for Order Group: $orderGroup!";
    } else {
        $errorMsg = "⚠ This chef is already assigned to this group!";
    }
}

// ================= Fetch chefs =================
$chefs = [];
$chefRes = $conn->query("SELECT chef_id, chef_name, status FROM Chef ORDER BY chef_name ASC");
while ($row = $chefRes->fetch_assoc()) {
    $chefs[] = $row;
}

// ================= Fetch pending order groups =================
$pendingGroups = [];
$groupRes = $conn->query("
    SELECT orderGroup, MAX(orderDate) AS lastOrderDate
    FROM customerOrders 
    WHERE status='Pending' 
    GROUP BY orderGroup
    ORDER BY lastOrderDate ASC
");
while ($g = $groupRes->fetch_assoc()) {
    $pendingGroups[] = $g['orderGroup'];
}

// ================= Fetch assigned chefs per group =================
$assignedChefs = [];
$assignedRes = $conn->query("
    SELECT k.orderGroup, c.chef_id, c.chef_name, k.status
    FROM KitchenJob k
    JOIN Chef c ON k.chef_id = c.chef_id
    ORDER BY k.orderGroup ASC
");
$allAssignedJobs = [];
while ($row = $assignedRes->fetch_assoc()) {
    $assignedChefs[$row['orderGroup']][$row['chef_id']] = $row['chef_name'];
    $allAssignedJobs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Kitchen Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body>

<div class="container mt-4">
    <a href="RestaurantManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    <h2 class="text-center mb-4">👨‍🍳 Assign Jobs to Chefs</h2>

    <?php 
    if (isset($successMsg)) echo "<div class='alert alert-success'>$successMsg</div>"; 
    if (isset($errorMsg)) echo "<div class='alert alert-warning'>$errorMsg</div>";
    ?>

    <!-- ================= Assign Form ================= -->
    <div class="card shadow p-4 mb-4">
        <h4>Assign Chef to Order Group</h4>
        <form method="POST" class="assign-form">
            <div class="row">
                <div class="col-md-5">
                    <label>Select Order Group</label>
                    <select name="order_group" id="orderGroupSelect" class="form-select" required>
                        <option value="">-- Choose Order Group --</option>
                        <?php foreach ($pendingGroups as $group): ?>
                            <option value="<?= $group ?>"><?= $group ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label>Select Chef</label>
                    <select name="chef_id" id="chefSelect" class="form-select" required>
                        <option value="">-- Choose Chef --</option>
                        <?php foreach ($chefs as $chef): ?>
                            <option value="<?= $chef['chef_id'] ?>"><?= $chef['chef_name'] ?> (<?= $chef['status'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="assign_job" class="btn btn-success w-100">Assign</button>
                </div>
            </div>
        </form>
    </div>

    <!-- ================= Assigned Jobs Table ================= -->
    <div class="card shadow p-4">
        <h4>Assigned Jobs</h4>
        <table class="table table-bordered text-center">
            <thead class="table-light">
                <tr>
                    <th>Order Group</th>
                    <th>Chef ID</th>
                    <th>Chef Name</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($allAssignedJobs) > 0): ?>
                    <?php foreach($allAssignedJobs as $job): ?>
                        <tr>
                            <td><?= $job['orderGroup'] ?></td>
                            <td><?= $job['chef_id'] ?></td>
                            <td><?= $job['chef_name'] ?></td>
                            <td><?= $job['status'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No assigned jobs yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// ================= Disable already assigned chefs dynamically =================
const assignedChefs = <?php echo json_encode($assignedChefs); ?>;

$('#orderGroupSelect').on('change', function() {
    const selectedGroup = $(this).val();
    const assigned = assignedChefs[selectedGroup] || {};

    $('#chefSelect option').each(function() {
        const chefId = $(this).val();
        if (assigned[chefId]) {
            $(this).prop('disabled', true).text($(this).text() + ' ✅ Assigned');
        } else {
            $(this).prop('disabled', false);
        }
    });
});
</script>

</body>
</html>
