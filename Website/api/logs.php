<?php
include 'db.php';
header("Content-Type: application/json");
$stmt = $conn->query("SELECT * FROM logs ORDER BY changed_at DESC");
echo json_encode($stmt->fetchAll());
?>
