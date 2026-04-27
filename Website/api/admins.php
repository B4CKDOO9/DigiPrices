<?php
    include 'db.php';
    header("Content-Type: application/json");
    if($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT id_admin, username, name, surname FROM admins";
        $result = $conn->query($sql);
        $rows = [];
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    echo json_encode($rows);
?>