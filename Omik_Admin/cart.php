<?php
session_start();
include 'config.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['userId'];

// ✅ Update quantity (AJAX)
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $cartId = intval($_POST['cartId']);
    $quantity = intval($_POST['quantity']);
    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE cartId=? AND userId=?");
        $stmt->bind_param("iii", $quantity, $cartId, $userId);
        $stmt->execute();
    }
    exit;
}

// ✅ Delete item (AJAX)
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $cartId = intval($_POST['cartId']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE cartId=? AND userId=?");
    $stmt->bind_param("ii", $cartId, $userId);
    $stmt->execute();
    exit;
}

// ✅ Fetch cart items
$stmt = $conn->prepare("SELECT * FROM cart WHERE userId=? ORDER BY addedAt DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$cartItems = $result->fetch_all(MYSQLI_ASSOC);

// ✅ Store for checkout
$_SESSION['checkoutCart'] = $cartItems;

// ✅ Calculate total
$grandTotal = 0;
foreach ($cartItems as $item) {
    $grandTotal += $item['quantity'] * $item['price'];
}
$_SESSION['checkoutTotal'] = $grandTotal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Cart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="cart.css" rel="stylesheet">
<style>
    /* Container styling */
.cart-container {
    max-width: 900px;
    margin: 40px auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Back button fixed styling */
.back-btn {
    position: absolute;
    top: 20px;
    left: 20px;
    font-weight: 500;
}

/* Table styling */
.table {
    border-radius: 8px;
    overflow: hidden;
}

.table th, .table td {
    vertical-align: middle;
}

.cart-item img {
    object-fit: cover;
    border-radius: 6px;
}

/* Input quantity styling */
.updateQty {
    text-align: center;
}

/* Delete button hover effect */
.deleteBtn {
    transition: 0.2s;
}

.deleteBtn:hover {
    transform: scale(1.1);
}

/* Grand total styling */
.cart-total {
    margin-top: 15px;
    font-size: 1.2rem;
}

/* Checkout button styling */
.btn-success {
    font-weight: 500;
    transition: 0.2s;
}

.btn-success:hover {
    transform: scale(1.05);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table thead {
        display: none;
    }
    .table, .table tbody, .table tr, .table td {
        display: block;
        width: 100%;
    }
    .table tr {
        margin-bottom: 15px;
        border-bottom: 1px solid #ddd;
    }
    .table td {
        text-align: right;
        padding-left: 50%;
        position: relative;
    }
    .table td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        width: 45%;
        font-weight: bold;
        text-align: left;
    }
    .cart-item {
        display: flex;
        align-items: center;
    }
}
.cart-item img {
    margin-right: 10px;
}
</style>
</head>
<body>

<div class="cart-container container mt-4">
    <h3 class="mb-4">🛒 My Cart</h3>
    <div class="text-center mt-3">
<a href="customerDashboard.php" class="btn btn-outline-primary back-btn">← Back</a>
        </div>

    <?php if (empty($cartItems)) : ?>
        <div class="alert alert-info text-center">Your cart is empty!</div>
    <?php else: ?>
        <table class="table align-middle text-center shadow-sm">
            <thead class="table-light">
                <tr>
                    <th>Food</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="cartBody">
            <?php foreach ($cartItems as $item): ?>
                <tr data-id="<?= $item['cartId'] ?>">
                    <td class="cart-item text-start">
                        <img src="<?= htmlspecialchars($item['foodImage']) ?>" alt="" width="50" height="50" class="rounded">
                        <span class="ms-2"><?= htmlspecialchars($item['foodName']) ?></span>
                    </td>
                    <td>Rs <?= number_format($item['price'], 2) ?></td>
                    <td>
                        <input type="number" min="1" value="<?= $item['quantity'] ?>" class="form-control form-control-sm w-50 mx-auto updateQty">
                    </td>
                    <td class="itemTotal">Rs <?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                    <td>
                        <button class="btn btn-danger btn-sm deleteBtn">❌</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-total mb-3 text-end fs-5 fw-bold">
            Grand Total: <span id="grandTotal">Rs <?= number_format($grandTotal, 2) ?></span>
        </div>

        <!-- Checkout Button -->
        <div class="text-end">
            <a href="checkout.php" class="btn btn-success px-4">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</div>

<!-- ✅ JavaScript for dynamic updates -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartBody = document.getElementById('cartBody');
    const grandTotalEl = document.getElementById('grandTotal');

    // Update quantity
    cartBody.addEventListener('change', async (e) => {
        if (e.target.classList.contains('updateQty')) {
            const tr = e.target.closest('tr');
            const cartId = tr.dataset.id;
            const quantity = parseInt(e.target.value);
            const priceText = tr.querySelector('td:nth-child(2)').textContent.replace('Rs', '').trim();
            const price = parseFloat(priceText);

            if (quantity > 0) {
                // Send AJAX update
                await fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'update',
                        cartId,
                        quantity
                    })
                });

                // Update totals
                const newTotal = price * quantity;
                tr.querySelector('.itemTotal').textContent = 'Rs ' + newTotal.toFixed(2);
                updateGrandTotal();
            }
        }
    });

    // Delete item
    cartBody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('deleteBtn')) {
            if (!confirm('Are you sure you want to remove this item?')) return;
            const tr = e.target.closest('tr');
            const cartId = tr.dataset.id;

            await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'delete',
                    cartId
                })
            });

            // Remove row from DOM
            tr.remove();
            updateGrandTotal();
        }
    });

    // Update grand total dynamically
    function updateGrandTotal() {
        let total = 0;
        document.querySelectorAll('.itemTotal').forEach(td => {
            const val = parseFloat(td.textContent.replace('Rs', '').trim());
            total += val;
        });
        grandTotalEl.textContent = 'Rs ' + total.toFixed(2);
    }
});
</script>
</body>
</html>
