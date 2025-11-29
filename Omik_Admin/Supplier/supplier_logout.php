<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to supplier login page
header("Location: supplier_login.php");
exit;
?>
