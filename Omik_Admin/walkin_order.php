<?php
session_start();
include 'config.php';

// ✅ Allow only Cashier
if (!isset($_SESSION['staffRole']) || strtolower(trim($_SESSION['staffRole'])) !== 'cashier') {
    header("Location: login.html");
    exit;
}

// ===== Fetch all available food items =====
$foodResult = $conn->query("SELECT * FROM food WHERE status='Have' ORDER BY foodName ASC");

// ===== Fetch all sizes =====
$foodSizes = [];
$sizeResult = $conn->query("SELECT * FROM food_size ORDER BY size ASC");
while($row = $sizeResult->fetch_assoc()){
    $foodSizes[$row['foodId']][] = $row; // store sizes per food
}

// ===== Handle Walk-in Order Submission =====
if (isset($_POST['create_bill'])) {
    $selected = $_POST['selected'] ?? [];
    $fullName = trim($_POST['fullName']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $paymentType = $_POST['paymentType'];

    $orderGroup = "WALKIN-" . time();
    $totalAmount = 0;

    foreach ($selected as $foodId) {
        $quantity = intval($_POST['quantity'][$foodId] ?? 0);
        if ($quantity <= 0) continue;

        $sizeId = $_POST['size'][$foodId] ?? 0;

        if($sizeId){
            $foodRow = $conn->query("SELECT price FROM food_size WHERE sizeId=$sizeId")->fetch_assoc();
        } else {
            $foodRow = $conn->query("SELECT price FROM food WHERE foodId=$foodId")->fetch_assoc();
        }

        $price = $foodRow['price'];
        $amount = $price * $quantity;
        $totalAmount += $amount;

        $stmt = $conn->prepare("
            INSERT INTO customerOrders (customerId, foodId, sizeId, quantity, amount, paymentType, paymentStatus, type, orderGroup)
            VALUES (0, ?, ?, ?, ?, ?, 'Paid', 'Walk-in', ?)
        ");
        $stmt->bind_param("iiisss", $foodId, $sizeId, $quantity, $amount, $paymentType, $orderGroup);
        $stmt->execute();
    }

    // Insert into billing table
    $billStmt = $conn->prepare("
        INSERT INTO billing (orderGroup, customerId, fullName, phone, address, paymentType, paymentStatus, totalAmount)
        VALUES (?, 0, ?, ?, ?, ?, 'Paid', ?)
    ");
    $billStmt->bind_param("sssssd", $orderGroup, $fullName, $phone, $address, $paymentType, $totalAmount);
    $billStmt->execute();

    echo "<script>
            alert('Walk-in order bill generated successfully!');
            window.location.href='print_bill.php?orderGroup=$orderGroup';
          </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Walk-in Order</title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f4f6f8; }
h2 { color: #333; text-align: center; }
form { max-width: 1000px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.form-group { margin-bottom: 15px; display: flex; align-items: center; }
.form-group label { width: 150px; font-weight: bold; }
.form-group input, .form-group select { flex: 1; padding: 8px; border-radius: 5px; border: 1px solid #ccc; }

table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
th { background: #007bff; color: white; }
input[type="number"] { width: 60px; }

.total-container { text-align: right; font-size: 18px; margin-top: 15px; font-weight: bold; }

button { padding: 12px 20px; border: none; border-radius: 5px; background: #28a745; color: white; cursor: pointer; margin-top: 15px; font-size: 16px; }
button:hover { background: #218838; }

#foodSearch { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; margin-bottom: 10px; }

.back-btn {
    position: fixed;       /* fixed on screen */
    top: 20px;             /* distance from top */
    left: 20px;            /* distance from left */
    background: #6c757d;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    z-index: 1000;         /* make sure it’s above other elements */
    font-size: 14px;
}
.back-btn:hover {
    background: #5a6268;
}

</style>

<script>
function updatePrice(select){
    const row = select.closest('tr');
    const price = parseFloat(select.selectedOptions[0].dataset.price);
    row.querySelector(".price").innerText = price.toFixed(2);
    calculateTotal();
}

function calculateTotal(){
    let total = 0;
    const rows = document.querySelectorAll("#foodTable tbody tr");
    rows.forEach(row => {
        const checkbox = row.querySelector("input[type='checkbox']");
        if(!checkbox.checked){ row.querySelector(".amount").innerText="0.00"; return; }

        const price = parseFloat(row.querySelector(".price").innerText);
        const qty = parseInt(row.querySelector("input[type='number']").value) || 0;
        const amount = price * qty;
        row.querySelector(".amount").innerText = amount.toFixed(2);
        total += amount;
    });
    document.getElementById("totalAmount").innerText = total.toFixed(2);
}

function filterFood() {
    const input = document.getElementById("foodSearch").value.toLowerCase();
    const rows = document.querySelectorAll("#foodTable tbody tr");
    rows.forEach(row => {
        const name = row.cells[1].innerText.toLowerCase();
        const id = row.querySelector("input[type='checkbox']").value;
        if(name.includes(input) || id.includes(input)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}
</script>
</head>
<body>
<button class="back-btn" onclick="goBack()">Back</button>
<script>
function goBack() {
    // Go back one page
    window.history.back();

    // Optional: force refresh after 100ms
    setTimeout(() => {
        location.reload();
    }, 100);
}
</script>
<h2>Walk-in Order</h2>

<form method="POST">
    <div class="form-group">
        <label>Customer Name:</label>
        <input type="text" name="fullName" placeholder="Optional">
    </div>
    <div class="form-group">
        <label>Phone:</label>
        <input type="text" name="phone" placeholder="Optional">
    </div>
    <div class="form-group">
        <label>Address:</label>
        <input type="text" name="address" placeholder="Optional">
    </div>
    <div class="form-group">
        <label>Payment Type:</label>
        <select name="paymentType" required>
            <option value="Cash">Cash</option>
            <option value="Card">Card</option>
            <option value="Online">Online</option>
        </select>
    </div>

    <div class="form-group">
        <label>Search Food:</label>
        <input type="text" id="foodSearch" placeholder="Search by name or ID" onkeyup="filterFood()">
    </div>

    <h3>Food Items</h3>
    <table id="foodTable">
        <thead>
            <tr>
                <th>Select</th>
                <th>Food Name</th>
                <th>Size</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php while($food = $foodResult->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" name="selected[]" value="<?= $food['foodId'] ?>" onchange="calculateTotal()"></td>
                <td><?= htmlspecialchars($food['foodName']) ?></td>
                <td>
                    <?php if(isset($foodSizes[$food['foodId']])): ?>
                        <select name="size[<?= $food['foodId'] ?>]" onchange="updatePrice(this)">
                            <?php foreach($foodSizes[$food['foodId']] as $s): ?>
                                <option value="<?= $s['sizeId'] ?>" data-price="<?= $s['price'] ?>"><?= $s['size'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td class="price"><?= isset($foodSizes[$food['foodId']]) ? number_format($foodSizes[$food['foodId']][0]['price'],2) : number_format($food['price'],2) ?></td>
                <td><input type="number" name="quantity[<?= $food['foodId'] ?>]" value="0" min="0" onchange="calculateTotal()"></td>
                <td class="amount">0.00</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="total-container">Total: Rs. <span id="totalAmount">0.00</span></div>

    <button type="submit" name="create_bill">Generate & Print Bill</button>
</form>

</body>
</html>
