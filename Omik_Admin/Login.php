<?php
session_start();
include 'config.php';

$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Please fill in all fields"]);
    exit;
}

// Query to check staff credentials
$sql = "SELECT * FROM Staff WHERE Email = ? AND Password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $staff = $result->fetch_assoc();

    // Save to session
    $_SESSION['staffId']   = $staff['StaffId'];
    $_SESSION['staffRole'] = $staff['JobRole']; // assume column name = JobRole
    $_SESSION['staffName'] = $staff['FirstName'] . ' ' . $staff['LastName'];

     // If delivery boy, get driver_id
    if (strtolower(trim($staff['JobRole'])) === 'delivery boy') {
        $stmt2 = $conn->prepare("SELECT driver_id FROM Driver WHERE driver_name=?");
        $stmt2->bind_param("s", $_SESSION['staffName']);
        $stmt2->execute();
        $res = $stmt2->get_result();
        if ($res->num_rows > 0) {
            $driver = $res->fetch_assoc();
            $_SESSION['driverId'] = $driver['driver_id'];
        }
        $stmt2->close();
    }

    // Map job roles to dashboard pages
    $role = strtolower(trim($staff['JobRole']));
    $dashboardPage = '';

    switch ($role) {
        case 'general manager':
            $dashboardPage = 'ManagerDashboard.php';
            break;
        case 'chef':
            $dashboardPage = 'ChefDashboard.php';
            break;
        case 'cashier':
            $dashboardPage = 'CashierDashboard.php';
            break;
        case 'hr manager':
            $dashboardPage = 'HRManagerDashboard.php';
            break;
        case 'stock keeper':
            $dashboardPage = 'StockKeeperDashboard.php';
            break;
        case 'accountant':
            $dashboardPage = 'AccountantDashboard.php';
            break;
        case 'inventory manager':
            $dashboardPage = 'InventoryManagerDashboard.php';
            break;
        case 'room keeper':
            $dashboardPage = 'RoomKeeperDashboard.php';
            break;
        case 'supervisor':
            $dashboardPage = 'SupervisorDashboard.php';
            break;
        case 'delivery boy':
            $dashboardPage = 'DeliveryDashboard.php';
            break;
        case 'receptionist':
            $dashboardPage = 'ReceptionDashboard.php';
            break;
        case 'owner':
            $dashboardPage = 'OwnerDashboard.php';
            break;
        case 'restaurant manager':
            $dashboardPage = 'RestaurantManagerDashboard.php';
            break;
        case 'transport manager':
            $dashboardPage = 'TransportManagerDashboard.php';
            break;
        default:
            $dashboardPage = 'StaffDashboard.php';
    }

    echo json_encode([
        "status" => "success",
        "message" => "Welcome, {$staff['JobRole']}",
        "type" => "staff",
        "redirect" => $dashboardPage
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password"
    ]);
}

$stmt->close();
$conn->close();
?>
