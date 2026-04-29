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

        // Log changes and insert price history BEFORE the curl loop
        // so the 30-day min query picks up the just-changed old price
        $changes = "";
        if ($price_old != $price) {
            $changes .= "Price: $price_old -> $price; ";
            $conn->query("INSERT INTO price_history (product_id, price, changed_at) VALUES ($id, $price_old, NOW())");
        }
        if ($name_old != $name) {
            $changes .= "Product name: $name_old -> $name; ";
        }

        // Push update to every display showing this product
        $displays_result = $conn->query("SELECT * FROM displays WHERE product_id = $id");
        while ($row = $displays_result->fetch_assoc()) {
            if (!empty($data['discount_per']) && !empty($data['discount_end']) && $data['discount_end'] > date('Y-m-d H:i:s')) {
                $dp      = floatval($data['discount_per']);
                $d_price = round($price * (1 - $dp / 100), 2);
                $hist    = $conn->query("SELECT MIN(price) AS lp FROM price_history WHERE product_id = $id AND changed_at >= NOW() - INTERVAL 30 DAY")->fetch_assoc();
                $low     = ($hist['lp'] !== null) ? min(floatval($hist['lp']), $price) : $price;
                $pl_discount_per   = number_format($dp, 2, '.', '');
                $pl_discount_price = number_format($d_price, 2, '.', '');
                $pl_lowest_price   = number_format($low, 2, '.', '');
                $pl_discount_end   = $data['discount_end'];
            } else {
                $pl_discount_per = $pl_discount_price = $pl_lowest_price = $pl_discount_end = null;
            }

            $url     = "http://" . $row['ip'] . "/update";
            $payload = json_encode([
                "id_display"    => strval($row['id_display']),
                "id"            => strval($id),
                "name"          => $data['name'],
                "price"         => number_format($price, 2, '.', ''),
                "price_per_kg"  => number_format($price_per_kg, 2, '.', ''),
                "unit"          => $unit,
                "quantity"      => $quantity > 0 ? number_format($quantity, 3, '.', '') : null,
                "barcode"       => $data['barcode'],
                "updated"       => date('Y-m-d H:i:s'),
                "discount_per"  => $pl_discount_per,
                "discount_price"=> $pl_discount_price,
                "lowest_price"  => $pl_lowest_price,
                "discount_end"  => $pl_discount_end,
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

        if ($changes != "") {
            $conn->query("INSERT INTO logs (id_log, admin_id, product_id, changed_at, what_changed) VALUES (NULL, $admin_id, $id, NOW(), '$changes')");
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

    // Clear every display that was showing this product before deleting
    $disp_res = $conn->query("SELECT id_display, ip FROM displays WHERE product_id = $id");
    while ($disp = $disp_res->fetch_assoc()) {
        if (!empty($disp['ip'])) {
            $clear = json_encode(["id_display" => strval($disp['id_display']), "clear" => true]);
            $ch = curl_init("http://" . $disp['ip'] . "/update");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $clear);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }
    }

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