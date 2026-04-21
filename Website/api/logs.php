<?php
include 'db.php';
header("Content-Type: application/json");
$sql = "SELECT * FROM logs ORDER BY changed_at DESC";
$result = $conn->query($sql);
$rows = [];
while($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows);
?>
