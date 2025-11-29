<?php
session_start();
include 'config.php';

// ✅ Allow only Chef
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'chef') {
    header("Location: login.html");
    exit;
}

// ✅ Get chef info
$chefName = $_SESSION['staffName'] ?? '';
if(empty($chefName)) die("Chef name not found in session!");

$chefQuery = $conn->prepare("SELECT * FROM Chef WHERE chef_name = ?");
$chefQuery->bind_param("s", $chefName);
$chefQuery->execute();
$chef = $chefQuery->get_result()->fetch_assoc();
if(!$chef) die("No chef found with this name!");

$chef_id = $chef['chef_id'];

// ✅ Handle AJAX update (Mark Group Ready)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_group'])) {
    $orderGroup = $_POST['order_group'];

    $conn->begin_transaction();
    try {
        // Update KitchenJob status
        $stmt1 = $conn->prepare("UPDATE KitchenJob SET status='Ready' WHERE orderGroup=? AND chef_id=?");
        $stmt1->bind_param("si", $orderGroup, $chef_id);
        $stmt1->execute();

        // Update all orders in this group
        $stmt2 = $conn->prepare("UPDATE customerOrders SET status='Ready for Serve' WHERE orderGroup=?");
        $stmt2->bind_param("s", $orderGroup);
        $stmt2->execute();

        // Set chef free
        $stmt3 = $conn->prepare("UPDATE Chef SET status='Free' WHERE chef_id=?");
        $stmt3->bind_param("i", $chef_id);
        $stmt3->execute();

        $conn->commit();
        echo json_encode(["status"=>"success", "message"=>"Order group marked as Ready and chef is Free."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status"=>"error", "message"=>"Update failed: ".$e->getMessage()]);
    }
    exit;
}

// ✅ Fetch assigned job groups for this chef
$sql = "
SELECT kj.orderGroup, kj.status AS job_status,
       GROUP_CONCAT(co.orderId) AS order_ids,
       GROUP_CONCAT(f.foodName, ' x', co.quantity SEPARATOR ', ') AS items,
       SUM(co.amount) AS total_amount,
       MAX(co.orderDate) AS lastOrderDate
FROM KitchenJob kj
JOIN customerOrders co ON kj.orderGroup = co.orderGroup
LEFT JOIN food f ON co.foodId = f.foodId
WHERE kj.chef_id = ?
GROUP BY kj.orderGroup, kj.status
ORDER BY lastOrderDate DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $chef_id);
$stmt->execute();
$result = $stmt->get_result();
$jobGroups = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chef Job Orders - Order Groups</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="viewJobOrders.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
/* ===== Status Badges ===== */
.status-pending { background-color: #ffc107; color: #000; padding: 3px 7px; border-radius: 5px; }
.status-in-progress { background-color: #0d6efd; color: #fff; padding: 3px 7px; border-radius: 5px; }
.status-ready { background-color: #198754; color: #fff; padding: 3px 7px; border-radius: 5px; }
.status-ready-for-serve { background-color: #20c997; color: #fff; padding: 3px 7px; border-radius: 5px; }

/* ===== Modal Styles ===== */
.modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.5);}
.modal-content { background:#fff; margin:10% auto; padding:20px; border-radius:10px; width:80%; max-width:600px; }
.close { color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer; }
.close:hover { color:#000; }
.table-hover tbody tr:hover { cursor:pointer; background:#f8f9fa; }
</style>
</head>
<body>
<div class="container">
    <a href="ChefDashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    <h1>🍽️ My Job Groups</h1>

    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Order Group</th>
                <th>Orders</th>
                <th>Items</th>
                <th>Total Amount</th>
                <th>Job Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($jobGroups)): ?>
                <tr><td colspan="6">No assigned job groups yet.</td></tr>
            <?php else: foreach($jobGroups as $group): 
                $jobStatusClass = strtolower(str_replace(' ', '-', $group['job_status']));
            ?>
            <tr data-items="<?= htmlspecialchars($group['items']) ?>" class="job-row">
                <td><?= htmlspecialchars($group['orderGroup']) ?></td>
                <td><?= htmlspecialchars($group['order_ids']) ?></td>
                <td><?= htmlspecialchars($group['items']) ?></td>
                <td>$<?= number_format($group['total_amount'], 2) ?></td>
                <td id="job-status-<?= htmlspecialchars($group['orderGroup']) ?>" class="status-<?= $jobStatusClass ?>"><?= $group['job_status'] ?></td>
                <td>
                    <?php if(strtolower($group['job_status']) !== 'ready'): ?>
                        <button class="ready-btn btn btn-success btn-sm" data-group="<?= htmlspecialchars($group['orderGroup']) ?>">Mark Ready</button>
                    <?php else: ?>
                        ✅ Ready
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- ===== Modal ===== -->
<div id="itemsModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h4>Food Items in this Job Group</h4>
    <p id="modalItems"></p>
  </div>
</div>

<script>
// ===== Mark Ready =====
$(document).on('click', '.ready-btn', function() {
    const group = $(this).data('group');
    if(confirm("Mark all jobs in this group as Ready?")) {
        $.post('viewJobOrders.php', { order_group: group }, function(response) {
            const res = JSON.parse(response);
            if(res.status === 'success') {
                $("#job-status-" + group).text("Ready").removeClass().addClass("status-ready");
                $("#row-" + group + " .ready-btn").replaceWith("✅ Ready");
            } else {
                alert(res.message);
            }
        });
    }
});

// ===== Show modal on row click =====
$('.job-row').on('click', function(e){
    // Prevent click on button triggering modal
    if($(e.target).hasClass('ready-btn')) return;

    const items = $(this).data('items');
    $('#modalItems').text(items);
    $('#itemsModal').fadeIn();
});

// ===== Close modal =====
$('.close').on('click', function(){ $('#itemsModal').fadeOut(); });
$(window).on('click', function(e){ if(e.target.id === 'itemsModal') $('#itemsModal').fadeOut(); });
</script>
</body>
</html>
