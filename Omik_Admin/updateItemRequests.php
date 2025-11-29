<?php
include 'config.php';

if (isset($_POST['id']) && isset($_POST['status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];

    $sql = "UPDATE NewItemRequests SET status = ? WHERE request_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo "✅ Request status updated to '$status' successfully!";
    } else {
        echo "❌ Error updating status.";
    }
}
?>
