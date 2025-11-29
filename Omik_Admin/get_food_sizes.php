<?php
include 'config.php';
$foodId = intval($_GET['foodId']);
$result = $conn->query("SELECT * FROM food_size WHERE foodId=$foodId");
$sizes = [];
while ($row = $result->fetch_assoc()) {
    $sizes[] = $row;
}
echo json_encode($sizes);
?>
