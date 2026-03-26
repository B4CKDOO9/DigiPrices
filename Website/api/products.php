<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM products";
    $result = $conn->query($sql);
    $rows = [];
    while($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode($rows);

} else if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $displaying_name = $conn->real_escape_string($data['displaying_name']);
    $name = $conn->real_escape_string($data['name']);
    $descr = $conn->real_escape_string($data['descr']);
    $price = floatval($data['price']);
    $price_per_kg = floatval($data['price_per_kg']);
    $currency_code = $conn->real_escape_string($data['currency_code']);
    $barcode = $conn->real_escape_string($data['barcode']);

    $sql = "INSERT INTO products (displaying_name, name, descr, price, price_per_kg, currency_code, barcode, last_price_change) 
            VALUES ('$displaying_name', '$name', '$descr', $price, $price_per_kg, '$currency_code', '$barcode', NOW())";
    try {
        $conn->query($sql);
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} else if($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = intval($data['id_product']);
    $displaying_name = $conn->real_escape_string($data['displaying_name']);
    $name = $conn->real_escape_string($data['name']);
    $descr = $conn->real_escape_string($data['descr']);
    $price = floatval($data['price']);
    $price_per_kg = floatval($data['price_per_kg']);
    $currency_code = $conn->real_escape_string($data['currency_code']);
    $barcode = $conn->real_escape_string($data['barcode']);

    $sql = "UPDATE products SET displaying_name='$displaying_name', name='$name', descr='$descr', 
            price=$price, price_per_kg=$price_per_kg, currency_code='$currency_code', barcode='$barcode', 
            last_price_change=NOW() WHERE id_product=$id";
    try {

    $conn->query($sql);
    $sql = "SELECT * FROM displays WHERE product_id = $id";
    $result = $conn->query($sql);
    
    while($row = $result->fetch_assoc()) {
    $url = "http://" . $row['ip'] . "/update";
    $payload = json_encode([
        "id"          => $id,
        "name"        => $data['name'],
        "price"       => $data['price'],
        "price_per_kg"=> $data['price_per_kg'],
        "barcode"     => $data['barcode'],
        "updated"     => date('Y-m-d H:i:s'),
    ]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_exec($ch);
    curl_close($ch);
    }

    echo json_encode(["success" => true]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} else if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($data['id_product']);
    $sql = "DELETE FROM products WHERE id_product = $id";
    try {
        $conn->query($sql);
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>