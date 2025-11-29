<?php
include 'config.php';

// ================== Handle New Item Request ==================
if(isset($_POST['place_request'])){
    $department = $_POST['department'];
    $item_id    = intval($_POST['item_id']);
    $quantity   = intval($_POST['quantity']);

    $stmt = $conn->prepare("INSERT INTO ItemRequests (department, item_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $department, $item_id, $quantity);
    $stmt->execute();
    $stmt->close();

    $successMsg = "✅ Request placed successfully!";
}

// ================== Handle Kitchen Request Status Update ==================
if(isset($_GET['kitchen_action'], $_GET['kitchen_id'])){
    $kid = intval($_GET['kitchen_id']);
    $action = $_GET['kitchen_action'];

    if($action === 'progress') $new_status = 'In Progress';
    elseif($action === 'complete') $new_status = 'Completed';
    else $new_status = 'Pending';

    $stmt = $conn->prepare("UPDATE KitchenRequest SET status=? WHERE request_id=?");
    $stmt->bind_param("si", $new_status, $kid);
    $stmt->execute();
    $stmt->close();

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); // redirect to remove GET params
    exit();
}

// ================== Fetch Item Requests (Kitchen) ==================
$sql_item = "SELECT ir.*, i.item_name 
             FROM ItemRequests ir 
             LEFT JOIN Items i ON ir.item_id = i.item_id
             WHERE ir.department = 'Kitchen'
             ORDER BY ir.request_date DESC";
$result_item = $conn->query($sql_item);
$itemRequests = [];
while($row = $result_item->fetch_assoc()){
    $itemRequests[] = $row;
}

// ================== Fetch Kitchen Requests ==================
$sql_kitchen = "SELECT * FROM KitchenRequest ORDER BY request_id DESC";
$result_kitchen = $conn->query($sql_kitchen);
$kitchenRequests = [];
while($row = $result_kitchen->fetch_assoc()){
    $kitchenRequests[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock & Kitchen Requests</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="requestIngredients.css">
<style>
.back-btn { display:inline-block; margin-bottom:10px; padding:5px 10px; background:#e74c3c; color:#fff; border-radius:5px; text-decoration:none; }
.status-pending { color:orange; font-weight:bold; }
.status-in\ progress { color:blue; font-weight:bold; }
.status-completed { color:green; font-weight:bold; }
.row-cols { display:flex; gap:20px; flex-wrap:wrap; }
.col-left, .col-right { flex:1; min-width:300px; background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { padding:8px; border:1px solid #ddd; text-align:center; }
th { background:#3498db; color:#fff; }
.btn-status { padding:3px 6px; font-size:0.9rem; border-radius:4px; text-decoration:none; margin:2px; display:inline-block; }
.btn-progress { background:blue; color:#fff; }
.btn-complete { background:green; color:#fff; }
</style>
</head>
<body>

<div class="container mt-4">
    <h2>📦 Stock & Kitchen Requests</h2>
    <a href="RestaurantManagerDashboard.php" class="back-btn">⬅ Back to Dashboard</a>

    <?php if(isset($successMsg)) echo "<div class='alert alert-success mt-2'>$successMsg</div>"; ?>

    <div class="row-cols mt-4">

        <!-- ===== Left Column: Place New Item Request ===== -->
        <div class="col-left">
            <h4>Place New Request</h4>
            <form method="POST">
                <label>Department</label>
                <select name="department" class="form-select mb-2" required>
                    <option value="">-- Select Department --</option>
                    <option value="Kitchen">Kitchen</option>
                    <option value="Bar">Bar</option>
                    <option value="Restaurant">Restaurant</option>
                    <option value="Housekeeping">Housekeeping</option>
                    <option value="Other">Other</option>
                </select>

                <label>Select Item</label>
                <select name="item_id" class="form-select mb-2" required>
                    <option value="">-- Select Item --</option>
                    <?php
                    $itemsResult = $conn->query("SELECT item_id, item_name FROM Items ORDER BY item_name ASC");
                    while($item = $itemsResult->fetch_assoc()){
                        echo "<option value='{$item['item_id']}'>{$item['item_name']}</option>";
                    }
                    ?>
                </select>

                <label>Quantity</label>
                <input type="number" name="quantity" class="form-control mb-2" min="1" placeholder="Enter quantity" required>

                <button type="submit" name="place_request" class="btn btn-primary w-100">Place Request</button>
            </form>
        </div>

        <!-- ===== Right Column: View Requests ===== -->
        <div class="col-right">
            <h4>Kitchen Item Requests</h4>
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Request Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($itemRequests) == 0){ ?>
                        <tr><td colspan="5">No requests found.</td></tr>
                    <?php } else {
                        foreach($itemRequests as $r){ 
                            $statusClass = strtolower($r['status']);
                            echo "<tr>
                                <td>{$r['request_id']}</td>
                                <td>{$r['item_name']}</td>
                                <td>{$r['quantity']}</td>
                                <td>{$r['request_date']}</td>
                                <td class='status-{$statusClass}'>{$r['status']}</td>
                            </tr>";
                        }
                    } ?>
                </tbody>
            </table>

            <h4 class="mt-4">Kitchen Direct Requests</h4>
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($kitchenRequests) == 0){ ?>
                        <tr><td colspan="5">No kitchen direct requests found.</td></tr>
                    <?php } else {
                        foreach($kitchenRequests as $r){ 
                            $statusClass = strtolower(str_replace(' ', '-', $r['status']));
                            echo "<tr>
                                <td>{$r['request_id']}</td>
                                <td>{$r['item_name']}</td>
                                <td>{$r['quantity']}</td>
                                <td class='status-{$statusClass}'>{$r['status']}</td>
                                <td>";
                            
                            if($r['status'] === 'Pending'){
                                echo "<a href='?kitchen_action=progress&kitchen_id={$r['request_id']}' class='btn-status btn-progress'>Mark In Progress</a>";
                            } elseif($r['status'] === 'In Progress'){
                                echo "<a href='?kitchen_action=complete&kitchen_id={$r['request_id']}' class='btn-status btn-complete'>Mark Completed</a>";
                            } else {
                                echo "✅ Completed";
                            }

                            echo "</td></tr>";
                        }
                    } ?>
                </tbody>
            </table>

        </div>

    </div>
</div>

</body>
</html>
