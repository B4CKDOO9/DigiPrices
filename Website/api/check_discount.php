<?php
    include 'db.php';
    $sql = "SELECT * FROM products WHERE discount_end IS NOT NULL  AND discount_end < NOW()";
    $res = $conn->query($sql);
    while($row = $res->fetch_assoc()) {
    $id = $row['id_product'];
    $update = "UPDATE products SET discount_per = NULL, discount_end = NULL WHERE id_product = $id";
    $conn->query($update);
    $sql_log = "INSERT INTO logs (admin_id, product_id, what_changed) VALUES (0, $id, 'Discount expired automatically')";
    $conn->query($sql_log);
    $displays = $conn->query("SELECT * FROM displays WHERE product_id = $id");
    while($display = $displays->fetch_assoc()) {
        $ip = $display['ip'];
        $url = "http://" . $ip . "/update";
        $payload = json_encode([
            "id_display"    => strval($display['id_display']),
            "id"            => strval($row['id_product']),
            "name"          => $row['name'],
            "price"         => number_format(floatval($row['price']), 2, '.', ''),
            "price_per_kg"  => number_format(floatval($row['price_per_kg']), 2, '.', ''),
            "unit"          => $row['unit'],
            "quantity"      => $row['quantity'] !== null ? number_format(floatval($row['quantity']), 3, '.', '') : null,
            "barcode"       => $row['barcode'],
            "updated"       => $row['last_price_change'],
            "discount_per"  => null,
            "discount_price"=> null,
            "lowest_price"  => null,
            "discount_end"  => null,
        ]);
        error_log("Sending payload: " . $payload);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
        }
    }
?>