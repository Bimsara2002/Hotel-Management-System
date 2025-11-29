<?php
session_start();
include 'config.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email == '' || $password == '') {
        $response = ['status' => 'error', 'message' => 'Please fill in all fields.'];
    } else {
        $stmt = $conn->prepare("SELECT * FROM Suppliers WHERE email = ? AND status = 'Active'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $supplier = $result->fetch_assoc();

            // ✅ Verify password using password_verify()
            if (password_verify($password, $supplier['password'])) {
                $_SESSION['supplier_id'] = $supplier['supplier_id'];
                $_SESSION['supplier_name'] = $supplier['supplier_name'];
                $_SESSION['supplier_email'] = $supplier['email'];

                $response = ['status' => 'success', 'message' => 'Login successful!', 'type' => 'supplier'];
            } else {
                $response = ['status' => 'error', 'message' => 'Invalid password.'];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'No active supplier found with that email.'];
        }
    }
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Omik Restaurant - Supplier Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* ===== Body & Background ===== */
body {
  background: url('https://t3.ftcdn.net/jpg/02/05/87/60/360_F_205876015_hYYs7ugqoU8QAobSS3TbnGQ92qyS5gEc.jpg') no-repeat center center/cover;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  margin: 0;
  font-family: Arial, sans-serif;
}

/* ===== Heading ===== */
h1 {
  color: #fff;
  margin-bottom: 30px;
  text-shadow: 2px 2px 5px rgba(0,0,0,0.6);
  text-align: center;
}

/* ===== Buttons ===== */
.btn-custom,
.btn-success {
  font-weight: 600;
  padding: 12px 30px;
  margin: 10px;
  border-radius: 8px;
  transition: 0.3s;
  width: 200px;
  text-align: center;
}

.btn-custom {
  background-color: #0d6efd;
  color: #fff;
}

.btn-custom:hover {
  background-color: #084298;
}

.btn-success {
  background-color: #198754;
  color: #fff;
}

.btn-success:hover {
  background-color: #146c43;
}

/* ===== Navbar ===== */
.navbar {
  width: 100%;
  position: fixed;
  top: 0;
  left: 0;
  background-color: #343a40;
  color: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 20px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
  z-index: 1000;
}

.navbar .navbar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 20px;
  font-weight: bold;
  color: white;
  text-decoration: none;
}

.navbar .navbar-brand img {
  width: 40px;
  height: 40px;
  border-radius: 5px;
  object-fit: cover;
}

.navbar a {
  color: white;
  margin-left: 20px;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s ease;
}

.navbar a:hover { color: #0d6efd; }

/* Responsive Navbar */
@media screen and (max-width: 768px) {
  .navbar {
    flex-direction: column;
    align-items: flex-start;
  }
  .navbar a {
    margin: 8px 0;
  }
}

/* ===== Buttons Container ===== */
.button-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  margin-top: 120px;
  text-align: center;
}

/* ===== Modal Styling ===== */
.modal-content {
  border-radius: 12px;
  padding: 25px 20px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

/* ===== Form Styling ===== */
.modal-body form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.modal-body label {
  font-weight: 600;
  margin-bottom: 5px;
  text-align: left;
}

.modal-body input {
  width: 100%;
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid #ced4da;
  outline: none;
  transition: 0.3s;
}

.modal-body input:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 5px rgba(13,110,253,0.3);
}

/* Button inside Modal */
.modal-body button {
  margin-top: 10px;
  font-weight: 600;
}

/* Responsive Modals */
@media screen and (max-width: 576px) {
  .modal-dialog {
    max-width: 90%;
  }
  .btn-custom,
  .btn-success {
    width: 100%;
  }
}
/* ===== Modal Styling ===== */
.modal-content {
  border-radius: 12px;
  padding: 20px;
}

/* ===== Form Horizontal Labels ===== */
.modal-body form .form-group {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
}

.modal-body form .form-group label {
  width: 120px;       /* Fixed width for labels */
  margin-right: 10px; /* Space between label and input */
  font-weight: 500;
  text-align: right;  /* Align text to right */
}

.modal-body form .form-group input,
.modal-body form .form-group textarea {
  flex: 1;             /* Input takes remaining space */
}

/* For smaller screens, stack labels above inputs */
@media screen and (max-width: 576px) {
  .modal-body form .form-group {
    flex-direction: column;
    align-items: stretch;
  }
  .modal-body form .form-group label {
    text-align: left;
    margin-bottom: 5px;
  }
}

</style>

</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="#">
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQE_SDa38sUEFcvtyP1PI2_Q7trbftFTGSOqA&s" alt="Logo">
        Omik Restaurant
      </a>
      <a href="#">About Us</a>
      <a href="#">Contact</a>
      <a href="#">Feedback</a>
    </div>
  </nav>

  <div style="margin-top: 120px; text-align: center;">
    <h1>Supplier Portal</h1>
    <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#supplierLoginModal">
      Supplier Login
    </button>
    
  </div>

  <!-- Supplier Login Modal -->
  <div class="modal fade" id="supplierLoginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Supplier Login</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="supplierLoginForm">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="supplierEmail" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" class="form-control" id="supplierPassword" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login as Supplier</button>
          </form>
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById("supplierLoginForm").addEventListener("submit", function(e){
    e.preventDefault();
    let email = document.getElementById("supplierEmail").value;
    let password = document.getElementById("supplierPassword").value;

    fetch('supplier_login.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.status === 'success') {
            window.location.href = 'SupplierDashboard.php';
        }
    })
    .catch(err => console.error(err));
});
</script>

</body>
</html>
