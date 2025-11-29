<?php
$servername = "127.0.0.1";   // or "localhost"
$username   = "Danuja";      // your MySQL Workbench username
$password   = "Danuja";      // your MySQL password
$dbname     = "OMIK";        // your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

