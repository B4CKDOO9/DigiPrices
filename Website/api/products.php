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
    $name = $conn->real_escape_string($data['name']);
    $price = floatval($data['price']);
    $unit = $conn->real_escape_string($data['unit'] ?? 'KOM');
    $quantity = floatval($data['quantity'] ?? 0);
    $price_per_kg = ($quantity > 0) ? round($price / $quantity, 2) : 0;
    $quantity_sql = ($quantity > 0) ? $quantity : 'NULL';
    $currency_code = $conn->real_escape_string($data['currency_code']);
    $barcode = $conn->real_escape_string($data['barcode']);
    $admin_id = $conn->real_escape_string($data['admin_id']);

    $sql = "INSERT INTO products (name, price, price_per_kg, currency_code, barcode, unit, quantity, last_price_change)
            VALUES ('$name', $price, $price_per_kg, '$currency_code', '$barcode', '$unit', $quantity_sql, NOW())";
    $sql_insert = "INSERT INTO logs (id_log, admin_id, product_id, changed_at, what_changed) VALUES (NULL, $admin_id, LAST_INSERT_ID(), NOW(), 'Created product $name with price: $price')";
    try {
        $conn->query($sql);
        $conn->query($sql_insert);
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} else if($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = intval($data['id_product']);
    $name = $conn->real_escape_string($data['name']);
    $price = floatval($data['price']);
    $unit = $conn->real_escape_string($data['unit'] ?? 'KOM');
    $quantity = floatval($data['quantity'] ?? 0);
    $price_per_kg = ($quantity > 0) ? round($price / $quantity, 2) : 0;
    $quantity_sql = ($quantity > 0) ? $quantity : 'NULL';
    $currency_code = $conn->real_escape_string($data['currency_code']);
    $barcode = $conn->real_escape_string($data['barcode']);
    $discount_per = !empty($data['discount_per']) ? floatval($data['discount_per']) : 'NULL';
    $discount_end = $conn->real_escape_string($data['discount_end'] ?? '');
    $discount_end_sql = empty($discount_end) ? 'NULL' : "'$discount_end'";
    $admin_id = $conn->real_escape_string($data['admin_id']);

    $old = $conn->query("SELECT * FROM products WHERE id_product = $id")->fetch_assoc();
    $price_old = $old['price'];
    $name_old = $old['name'];

    $sql = "UPDATE products SET name='$name',
            price=$price, price_per_kg=$price_per_kg, currency_code='$currency_code', barcode='$barcode',
            unit='$unit', quantity=$quantity_sql,
            discount_per=$discount_per, discount_end=$discount_end_sql, last_price_change=NOW() WHERE id_product=$id";
    try {

    $conn->query($sql);
    $sql = "SELECT * FROM displays WHERE product_id = $id";
    $result = $conn->query($sql);
    
    while($row = $result->fetch_assoc()) {
    if(!empty($data['discount_per']) && $data['discount_end'] > date('Y-m-d H:i:s')) {
        $discount_price = $data['price'] * (1 - $data['discount_per'] / 100);
        $sql = "SELECT MIN(price) AS lowest_price FROM price_history WHERE product_id = $id AND changed_at >= NOW() - INTERVAL 30 DAY";
        $lowest_price = $conn->query($sql)->fetch_assoc();
        $min_price = $lowest_price['lowest_price'] ?? $data['price'];
    } else {
        $discount_price = null;
        $min_price = null;
    }
    $url = "http://" . $row['ip'] . "/update";
    $payload = json_encode([
        "id_display"    => $row['id_display'],
        "id"            => $id,
        "name"          => $data['name'],
        "price"         => $data['price'],
        "price_per_kg"  => $price_per_kg,
        "unit"          => $unit,
        "quantity"      => $quantity > 0 ? $quantity : null,
        "barcode"       => $data['barcode'],
        "updated"       => date('Y-m-d H:i:s'),
        "discount_per"  => $data['discount_per'],
        "discount_price"=> strval($discount_price),
        "lowest_price"  => strval($min_price),
        "discount_end"  => $data['discount_end'],
    ]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    }

    $changes = "";
    if($price_old != $price) {
        $changes .= "Price: $price_old -> $price; ";
        $sql = "INSERT INTO price_history (id_history, product_id, price, changed_at) VALUES (NULL, $id, $price_old, NOW())";
        $conn->query($sql);
    }
    if($name_old != $name) {
        $changes .= "Product name: $name_old -> $name; ";
    }
    if($changes != "") {
        $sql_insert = "INSERT INTO logs (id_log, admin_id, product_id, changed_at, what_changed) VALUES (NULL, $admin_id, $id, NOW(), '$changes')";  
        $conn->query($sql_insert);
    }

    echo json_encode(["success" => true]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} else if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($data['id_product']);
    $admin_id = $conn->real_escape_string($data['admin_id']);

    $old = $conn->query("SELECT * FROM products WHERE id_product = $id")->fetch_assoc();
    $price_old = $old['price'];
    $name_old = $old['name'];
    
    $sql_insert = "INSERT INTO logs (id_log, admin_id, product_id, changed_at, what_changed) VALUES (NULL, $admin_id, $id, NOW(), 'Deleted product $name_old with price: $price_old')";
    $sql = "DELETE FROM products WHERE id_product = $id";
    try {
        $conn->query($sql_insert);
        $conn->query($sql);
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>