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
    $mac = $conn->real_escape_string($data['mac']);
    $ip = $conn->real_escape_string($data['ip']);
    $fw_version = $conn->real_escape_string($data['fw_version']);
    $admin_id = intval($data['admin_id']);

    $sql = "INSERT INTO displays (section, mac, ip, fw_version) VALUES ('$section', '$mac', '$ip', '$fw_version')";
    $sql_insert = "INSERT INTO logs (id_log, admin_id, display_id, changed_at, what_changed) VALUES (NULL, $admin_id, LAST_INSERT_ID(), NULL, 'Added display with IP: $ip')";
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
    $sql_insert = "INSERT INTO logs (id_log, admin_id, display_id, changed_at, what_changed) VALUES (NULL, $admin_id, $id, NULL, 'Deleted display with IP: $ip_old')";
    $sql = "DELETE FROM displays WHERE id_display = $id";
    try {
        $conn->query($sql_insert);
        $conn->query($sql);
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}  else if($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = intval($data['id_display']);
    $section = $conn->real_escape_string($data['section']);
    $mac = $conn->real_escape_string($data['mac']);
    $ip = $conn->real_escape_string($data['ip']);
    $fw_version = $conn->real_escape_string($data['fw_version']);
    $product_id = !empty($data['product_id']) ? intval($data['product_id']) : 'NULL';
    $admin_id = intval($data['admin_id']);

    $old = $conn->query("SELECT * FROM displays WHERE id_display = $id")->fetch_assoc();
    $ip_old = $old['ip'];
    $product_id_old = $old['product_id'];
    $section_old = $old['section'];

    $sql = "UPDATE displays SET section='$section', mac='$mac', ip='$ip', fw_version='$fw_version', product_id=$product_id WHERE id_display=$id";

    $changes = "";
    if($ip_old != $ip) {
        $changes .= "IP changed from $ip_old to $ip; ";    
    }
    if ($product_id_old != $product_id) {
        $changes .= "Product changed from $product_id_old to $product_id;";
    }
    if ($section_old != $section) {
        $changes .= "Section changed from $section_old to $section";
    }
    if($changes != "") {
        $sql_insert = "INSERT INTO logs (id_log, admin_id, display_id, changed_at, what_changed) VALUES (NULL, $admin_id, $id, NULL, '$changes')";
        $conn->query($sql_insert);
    }


    try {
        $conn->query($sql);

        if(!empty($data['product_id'])) {
            $sql = "SELECT * FROM products WHERE id_product = $product_id";
            $result = $conn->query($sql);
            $product = $result->fetch_assoc();
            
            $url = "http://" . $ip . "/update";
            $payload = json_encode([
                "id"          => $product['id_product'],
                "name"        => $product['name'],
                "price"       => $product['price'],
                "price_per_kg"=> $product['price_per_kg'],
                "barcode"     => $product['barcode'],
                "updated"     => $product['last_price_change'],
                ]);
                
            $ch = curl_init($url);                    // create curl request to $url
            curl_setopt($ch, CURLOPT_POST, true);     // make it a POST
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);           // attach the JSON
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);  // tell it we're sending JSON
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);   // how many seconds before giving up?
            curl_exec($ch);                           // send it!
            curl_close($ch);    // clean up
        }

        echo json_encode(["success" => true]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }


}
?> 
