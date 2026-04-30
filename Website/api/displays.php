<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);



if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM displays";
    $result = $conn->query($sql);
    $rows = [];
    while($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode($rows);
}  else if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $conn->real_escape_string($data['section']);
    $ip = $conn->real_escape_string($data['ip']);
    $admin_id = intval($data['admin_id']);

    $sql = "INSERT INTO displays (section, ip) VALUES ('$section', '$ip')";
    $sql_insert = "INSERT INTO logs (id_log, admin_id, display_id, changed_at, what_changed) VALUES (NULL, $admin_id, LAST_INSERT_ID(), NOW(), 'Added display with IP: $ip')";
    try {
        $conn->query($sql);
        $conn->query($sql_insert);
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}  else if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($data['id_display']);
    $admin_id = intval($data['admin_id']);

    $old = $conn->query("SELECT * FROM displays WHERE id_display = $id")->fetch_assoc();
    $ip_old = $old['ip'];

    // Clear the display screen before removing it
    if (!empty($ip_old)) {
        $clear = json_encode(["id_display" => strval($id), "clear" => true]);
        $ch = curl_init("http://" . $ip_old . "/update");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $clear);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    $sql_insert = "INSERT INTO logs (id_log, admin_id, display_id, changed_at, what_changed) VALUES (NULL, $admin_id, $id, NOW(), 'Deleted display with IP: $ip_old')";
    $sql = "DELETE FROM displays WHERE id_display = $id";
    try {
        $conn->query($sql_insert);
        $conn->query($sql);
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}  else if($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = strval($data['id_display']);
    $section = $conn->real_escape_string($data['section']);
    $ip = $conn->real_escape_string($data['ip']);
    $product_id = !empty($data['product_id']) ? intval($data['product_id']) : 'NULL';
    $admin_id = intval($data['admin_id']);

    $old = $conn->query("SELECT * FROM displays WHERE id_display = $id")->fetch_assoc();
    $ip_old = $old['ip'];
    $product_id_old = $old['product_id'];
    $section_old = $old['section'];

    $sql = "UPDATE displays SET section='$section', ip='$ip',product_id=$product_id WHERE id_display=$id";

    $changes = "";
    if($ip_old != $ip) {
        $changes .= "IP changed from $ip_old to $ip; ";    
    }
    if ($product_id_old != $product_id) {
        $changes .= "Product shown changed from (#$product_id_old) to (#$product_id);";
    }
    if ($section_old != $section) {
        $changes .= "Section changed from $section_old to $section";
    }

    if ($changes != "") {
        $sql_insert = "INSERT INTO logs (id_log, admin_id, display_id, product_id, changed_at, what_changed) VALUES (NULL, $admin_id, $id, $product_id, NOW(), '$changes')";
        $conn->query($sql_insert);
    }


    try {
        $conn->query($sql);

        if(!empty($data['product_id'])) {
            $sql = "SELECT * FROM products WHERE id_product = $product_id";
            $result = $conn->query($sql);
            $product = $result->fetch_assoc();
            if (!empty($product['discount_per']) && $product['discount_end'] > date('Y-m-d H:i:s')) {
                $dp      = floatval($product['discount_per']);
                $d_price = round(floatval($product['price']) * (1 - $dp / 100), 2);
                $hist    = $conn->query("SELECT MIN(price) AS lp FROM price_history WHERE product_id = $product_id AND changed_at >= NOW() - INTERVAL 30 DAY")->fetch_assoc();
                $low     = ($hist['lp'] !== null) ? min(floatval($hist['lp']), floatval($product['price'])) : floatval($product['price']);
                $pl_discount_per   = number_format($dp, 2, '.', '');
                $pl_discount_price = number_format($d_price, 2, '.', '');
                $pl_lowest_price   = number_format($low, 2, '.', '');
                $pl_discount_end   = $product['discount_end'];
            } else {
                $pl_discount_per = $pl_discount_price = $pl_lowest_price = $pl_discount_end = null;
            }
            $url = "http://" . $ip . "/update";
            $payload = json_encode([
                "id_display"    => strval($id),
                "id"            => strval($product['id_product']),
                "name"          => $product['name'],
                "price"         => number_format(floatval($product['price']), 2, '.', ''),
                "price_per_kg"  => number_format(floatval($product['price_per_kg']), 2, '.', ''),
                "unit"          => $product['unit'],
                "quantity"      => $product['quantity'] !== null ? number_format(floatval($product['quantity']), 3, '.', '') : null,
                "barcode"       => $product['barcode'],
                "updated"       => !empty($product['last_price_change']) ? date('d-m-Y', strtotime($product['last_price_change'])) : null,
                "discount_per"  => $pl_discount_per,
                "discount_price"=> $pl_discount_price,
                "lowest_price"  => $pl_lowest_price,
                "discount_end"  => $pl_discount_end ? date('d-m-Y', strtotime($pl_discount_end)) : null
                ]);
            error_log("Sending payload: " . $payload);
            $ch = curl_init($url);                    // create curl request to $url
            curl_setopt($ch, CURLOPT_POST, true);     // make it a POST
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);           // attach the JSON
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);  // tell it we're sending JSON
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);   // how many seconds before giving up?
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);                           // send it!
            curl_close($ch);    // clean up
        }

        echo json_encode(["success" => true]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }


}
?> 
