<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query("SELECT * FROM displays");
    echo json_encode($stmt->fetchAll());

} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section  = $data['section'] ?? '';
    $ip       = $data['ip']      ?? '';
    $admin_id = intval($data['admin_id']);

    try {
        $stmt = $conn->prepare("INSERT INTO displays (section, ip) VALUES (?, ?)");
        $stmt->execute([$section, $ip]);
        $display_id = $conn->lastInsertId();

        $log = $conn->prepare("INSERT INTO logs (id_log, admin_id, display_id, changed_at, what_changed) VALUES (NULL, ?, ?, NOW(), ?)");
        $log->execute([$admin_id, $display_id, "Added display with IP: $ip"]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id       = intval($data['id_display']);
    $admin_id = intval($data['admin_id']);

    $stmt = $conn->prepare("SELECT * FROM displays WHERE id_display = ?");
    $stmt->execute([$id]);
    $old    = $stmt->fetch();
    $ip_old = $old['ip'] ?? '';

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

    try {
        $log = $conn->prepare("INSERT INTO logs (id_log, admin_id, display_id, changed_at, what_changed) VALUES (NULL, ?, ?, NOW(), ?)");
        $log->execute([$admin_id, $id, "Deleted display with IP: $ip_old"]);

        $del = $conn->prepare("DELETE FROM displays WHERE id_display = ?");
        $del->execute([$id]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id         = intval($data['id_display']);
    $section    = $data['section']    ?? '';
    $ip         = $data['ip']         ?? '';
    $product_id = !empty($data['product_id']) ? intval($data['product_id']) : null;
    $admin_id   = intval($data['admin_id']);

    $stmt = $conn->prepare("SELECT * FROM displays WHERE id_display = ?");
    $stmt->execute([$id]);
    $old            = $stmt->fetch();
    $ip_old         = $old['ip']         ?? '';
    $product_id_old = $old['product_id'] ?? null;
    $section_old    = $old['section']    ?? '';

    $changes = "";
    if ($ip_old != $ip)                  $changes .= "IP changed from $ip_old to $ip; ";
    if ($product_id_old != $product_id)  $changes .= "Product shown changed from (#$product_id_old) to (#$product_id);";
    if ($section_old != $section)        $changes .= "Section changed from $section_old to $section";

    try {
        $upd = $conn->prepare("UPDATE displays SET section=?, ip=?, product_id=? WHERE id_display=?");
        $upd->execute([$section, $ip, $product_id, $id]);

        if ($changes !== "") {
            $log = $conn->prepare("INSERT INTO logs (id_log, admin_id, display_id, product_id, changed_at, what_changed) VALUES (NULL, ?, ?, ?, NOW(), ?)");
            $log->execute([$admin_id, $id, $product_id, $changes]);
        }

        if ($product_id !== null && !empty($ip)) {
            $pstmt = $conn->prepare("SELECT * FROM products WHERE id_product = ?");
            $pstmt->execute([$product_id]);
            $product = $pstmt->fetch();

            if ($product) {
                if (!empty($product['discount_per']) && $product['discount_end'] > date('Y-m-d H:i:s')) {
                    $dp      = floatval($product['discount_per']);
                    $d_price = round(floatval($product['price']) * (1 - $dp / 100), 2);
                    $hstmt   = $conn->prepare("SELECT MIN(price) AS lp FROM price_history WHERE product_id = ? AND changed_at >= NOW() - INTERVAL 30 DAY");
                    $hstmt->execute([$product_id]);
                    $h   = $hstmt->fetch();
                    $low = ($h['lp'] !== null) ? min(floatval($h['lp']), floatval($product['price'])) : floatval($product['price']);
                    $pl_discount_per   = number_format($dp, 2, '.', '');
                    $pl_discount_price = number_format($d_price, 2, '.', '');
                    $pl_lowest_price   = number_format($low, 2, '.', '');
                    $pl_discount_end   = $product['discount_end'];
                } else {
                    $pl_discount_per = $pl_discount_price = $pl_lowest_price = $pl_discount_end = null;
                }

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
                    "discount_end"  => $pl_discount_end ? date('d-m-Y', strtotime($pl_discount_end)) : null,
                ]);
                $ch = curl_init("http://" . $ip . "/update");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                curl_close($ch);
            }
        }

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>
