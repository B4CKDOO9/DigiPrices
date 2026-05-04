<?php
include 'db.php';
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query("SELECT id_admin, username, name, surname FROM admins");
    echo json_encode($stmt->fetchAll());
}
?>
