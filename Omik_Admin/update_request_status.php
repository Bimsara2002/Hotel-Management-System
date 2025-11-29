<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($id > 0 && in_array($status, ['Approved', 'Rejected'])) {
        $stmt = $conn->prepare("UPDATE NewItemRequests SET status=? WHERE request_id=?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            echo "Request status updated to $status.";
        } else {
            echo "Failed to update status: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "Invalid request.";
    }
} else {
    echo "Invalid method.";
}
?>
