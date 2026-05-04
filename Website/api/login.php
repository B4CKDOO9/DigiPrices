<?php
include 'db.php';
header("Content-Type: application/json");
$data     = json_decode(file_get_contents("php://input"), true);
$username = $data["username"] ?? '';
$password = $data["password"] ?? '';

$stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND password = ?");
$stmt->execute([$username, $password]);
$row = $stmt->fetch();

if ($row) {
    echo json_encode(["success" => true, "admin" => $row]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid credentials"]);
}
?>
