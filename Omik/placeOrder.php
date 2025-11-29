<?php
session_start();
include 'config.php'; // Your DB connection

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_SESSION['customerId'] ?? 1; // Replace with logged-in customer ID
    $foodId = $_POST['foodId'];
    $quantity = (int)$_POST['quantity'];

    // Fetch food price
    $stmt = $conn->prepare("SELECT price FROM Food WHERE foodId=? AND status='Available'");
    $stmt->bind_param("i", $foodId);
    $stmt->execute();
    $stmt->bind_result($price);
    $stmt->fetch();
    $stmt->close();

    if ($price && $quantity > 0) {
        $amount = $price * $quantity;

        $stmt = $conn->prepare("INSERT INTO Orders (customerId, foodId, quantity, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $customerId, $foodId, $quantity, $amount);
        if ($stmt->execute()) {
            $success = "Order placed successfully!";
        } else {
            $error = "Failed to place order. Try again.";
        }
        $stmt->close();
    } else {
        $error = "Invalid food selection or quantity.";
    }
}

// Fetch available food items
$foods = $conn->query("SELECT * FROM Food WHERE status='Available' ORDER BY foodName ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Place Order - Omik Restaurant</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #fdfbfb, #ebedee);
    margin: 0;
    padding: 0;
}
header {
    background: #2c3e50;
    color: #fff;
    padding: 20px;
    text-align: center;
    font-size: 24px;
    font-weight: 600;
}
.container {
    max-width: 900px;
    margin: 30px auto;
    padding: 20px;
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
h2 {
    color: #34495e;
    margin-bottom: 20px;
}
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
select, input[type="number"] {
    padding: 12px;
    font-size: 15px;
    border-radius: 10px;
    border: 1px solid #ccc;
}
select:focus, input:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 8px rgba(52,152,219,0.3);
}
button {
    padding: 12px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: #fff;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 16px;
    transition: 0.3s;
}
button:hover {
    background: linear-gradient(135deg, #2980b9, #2471a3);
    transform: translateY(-2px);
}
.message {
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}
.success { background: #2ecc71; color: #fff; }
.error { background: #e74c3c; color: #fff; }
.total-amount {
    font-weight: 600;
    font-size: 16px;
    color: #34495e;
}
</style>
</head>
<body>

<header>Place Your Order</header>

<div class="container">
    <h2>Order Food</h2>

    <?php if(!empty($success)) echo "<div class='message success'>$success</div>"; ?>
    <?php if(!empty($error)) echo "<div class='message error'>$error</div>"; ?>

    <form method="POST" id="orderForm">
        <label for="foodId">Select Food</label>
        <select name="foodId" id="foodId" required>
            <option value="">-- Choose Food --</option>
            <?php while($row = $foods->fetch_assoc()): ?>
                <option value="<?= $row['foodId'] ?>" data-price="<?= $row['price'] ?>">
                    <?= htmlspecialchars($row['foodName']) ?> - $<?= number_format($row['price'],2) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="quantity">Quantity</label>
        <input type="number" name="quantity" id="quantity" min="1" value="1" required>

        <div class="total-amount">Total Amount: $<span id="total">0.00</span></div>

        <button type="submit">Place Order</button>
    </form>
</div>

<script>
const foodSelect = document.getElementById('foodId');
const quantityInput = document.getElementById('quantity');
const totalDisplay = document.getElementById('total');

function updateTotal() {
    const price = parseFloat(foodSelect.selectedOptions[0].dataset.price || 0);
    const qty = parseInt(quantityInput.value || 1);
    totalDisplay.textContent = (price * qty).toFixed(2);
}

foodSelect.addEventListener('change', updateTotal);
quantityInput.addEventListener('input', updateTotal);
updateTotal();
</script>

</body>
</html>
<?php $conn->close(); ?>
