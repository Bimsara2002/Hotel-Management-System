<?php
include 'config.php';

if(!isset($_GET['billId'])) exit('Invalid request');

$billId = intval($_GET['billId']);

// Get bill info
$bill = $conn->query("SELECT * FROM billing WHERE billingId=$billId")->fetch_assoc();
if(!$bill) exit('Bill not found');

// Get all items from this order group
$items = $conn->query("
    SELECT co.*, f.foodName, fs.size
    FROM customerOrders co
    LEFT JOIN food f ON co.foodId = f.foodId
    LEFT JOIN food_size fs ON co.sizeId = fs.sizeId
    WHERE co.orderGroup='{$bill['orderGroup']}'
");
?>

<div id="billContent" style="width:400px; margin:auto; font-family: Arial, sans-serif; background:#fff; padding:20px; border:1px solid #ccc; border-radius:5px;">
    <div style="text-align:center; margin-bottom:15px;">
        <h2 style="margin:0;">Omik Family Restaurant</h2>
        <small>Cashier Bill Receipt</small>
    </div>

    <div style="margin-bottom:15px;">
        <p><strong>Bill ID:</strong> <?= $bill['billingId'] ?></p>
        <p><strong>Order Group:</strong> <?= htmlspecialchars($bill['orderGroup']) ?></p>
        <p><strong>Date:</strong> <?= date('d-m-Y H:i', strtotime($bill['createdAt'])) ?></p>
    </div>

    <div style="margin-bottom:15px;">
        <p><strong>Customer:</strong> <?= htmlspecialchars($bill['fullName']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($bill['phone']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($bill['address']) ?></p>
    </div>

    <table style="width:100%; border-collapse: collapse; margin-bottom:15px;">
        <thead>
            <tr style="border-bottom:1px solid #000;">
                <th style="text-align:left;">Item</th>
                <th>Size</th>
                <th>Qty</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $items->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['foodName']) ?></td>
                <td style="text-align:center;"><?= $row['size'] ?? 'N/A' ?></td>
                <td style="text-align:center;"><?= $row['quantity'] ?></td>
                <td style="text-align:right;">Rs. <?= number_format($row['amount'],2) ?></td>
            </tr>
            <?php endwhile; ?>
            <tr style="border-top:1px solid #000;">
                <td colspan="3" style="text-align:right; font-weight:bold;">Total</td>
                <td style="text-align:right; font-weight:bold;">Rs. <?= number_format($bill['totalAmount'],2) ?></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-bottom:10px;">
        <p><strong>Payment Type:</strong> <?= $bill['paymentType'] ?></p>
        <p><strong>Status:</strong> <?= $bill['paymentStatus'] ?></p>
    </div>

    <div style="text-align:center; font-size:12px; margin-top:15px;">
        Thank you for dining with us!
    </div>
</div>

<div style="text-align:center; margin-top:15px;">
    <button onclick="printBill()" style="padding:10px 20px; background:#28a745; color:#fff; border:none; border-radius:5px; cursor:pointer;">Print Bill</button>
</div>

<script>
function printBill(){
    const content = document.getElementById('billContent').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=400');
    printWindow.document.write('<html><head><title>Print Bill</title>');
    printWindow.document.write('<style>body{font-family:Arial;} table{width:100%; border-collapse:collapse;} th,td{text-align:left; padding:5px;} th{text-align:left;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>
