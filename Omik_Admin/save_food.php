<?php
include 'config.php';

$foodId = $_POST['foodId'] ?? '';
$foodName = $_POST['foodName'] ?? '';
$description = $_POST['description'] ?? '';
$category = $_POST['category'] ?? '';
$status = $_POST['status'] ?? '';
$imagePath = '';

if (!empty($_FILES['image']['name'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) mkdir($targetDir);
    $imagePath = $targetDir . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
}

// If updating
if (!empty($foodId)) {
    $sql = "UPDATE food SET foodName=?, discription=?, category=?, status=?, image=? WHERE foodId=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $foodName, $description, $category, $status, $imagePath, $foodId);
    $stmt->execute();

    // Update food sizes
    $conn->query("DELETE FROM food_size WHERE foodId=$foodId");
    foreach ($_POST['sizes'] as $size => $price) {
        if (!empty($price)) {
            $conn->query("INSERT INTO food_size (foodId, size, price) VALUES ($foodId, '$size', $price)");
        }
    }
    echo "Food updated successfully.";
} else {
    // New insert
    $sql = "INSERT INTO food (foodName, discription, category, status, image) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $foodName, $description, $category, $status, $imagePath);
    $stmt->execute();
    $foodId = $conn->insert_id;

    // Add sizes
    foreach ($_POST['sizes'] as $size => $price) {
        if (!empty($price)) {
            $conn->query("INSERT INTO food_size (foodId, size, price) VALUES ($foodId, '$size', $price)");
        }
    }
    echo "New food added successfully.";
}
?>
