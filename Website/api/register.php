<?php
include 'db.php';
header("Content-Type: application/json");
$data = json_decode(file_get_contents('php://input'), true);
$ip   = $data['ip'] ?? '';

if (empty($ip)) {
    echo json_encode(["success" => false, "message" => "No IP provided"]);
    exit;
}

$stmt = $conn->prepare("SELECT id_display FROM displays WHERE ip = ?");
$stmt->execute([$ip]);
if ($stmt->fetch()) {
    echo json_encode(["success" => true, "message" => "Display already registered"]);
} else {
    $ins = $conn->prepare("INSERT INTO displays (ip) VALUES (?)");
    $ins->execute([$ip]);
    echo json_encode(["success" => true, "message" => "Display registered"]);
}
?>
