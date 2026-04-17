<?php
include 'db.php';
header("Content-Type: application/json");
$data = json_decode(file_get_contents('php://input'),true);
$ip = $data['ip'];
$sql = "SELECT * FROM displays WHERE ip = '$ip'";
$result = $conn->query($sql);
if($result->num_rows > 0) {
    echo json_encode(["success" => true, "message" => "Display already registered"]);
} else {
    $sql = "INSERT INTO displays (ip) VALUES ('$ip')";
    $conn->query($sql);
    echo json_encode(["success" => true, "message" => "Display registered successfully"]);
}
?>