<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';
header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->query("SELECT * FROM products");
    echo json_encode($stmt->fetchAll());

} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = $data['name']          ?? '';
    $price         = floatval($data['price'] ?? 0);
    $unit          = $data['unit']          ?? 'KOM';
    $quantity      = floatval($data['quantity'] ?? 0);
    $price_per_kg  = ($quantity > 0) ? round($price / $quantity, 2) : 0;
    $quantity_val  = ($quantity > 0) ? $quantity : null;
    $currency_code = $data['currency_code'] ?? 'EUR';
    $barcode       = $data['barcode']       ?? '';
    $admin_id      = intval($data['admin_id']);

    try {
        $stmt = $conn->prepare(
            "INSERT INTO products (name, price, price_per_kg, currency_code, barcode, unit, quantity, last_price_change)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$name, $price, $price_per_kg, $currency_code, $barcode, $unit, $quantity_val]);
        $product_id = $conn->lastInsertId();

        $log = $conn->prepare(
            "INSERT INTO logs (id_log, admin_id, product_id, changed_at, what_changed)
             VALUES (NULL, ?, ?, NOW(), ?)"
        );
        $log->execute([$admin_id, $product_id, "Created product $name with price: $price"]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id            = intval($data['id_product']);
    $name          = $data['name']          ?? '';
    $price         = floatval($data['price'] ?? 0);
    $unit          = $data['unit']          ?? 'KOM';
    $quantity      = floatval($data['quantity'] ?? 0);
    $price_per_kg  = ($quantity > 0) ? round($price / $quantity, 2) : 0;
    $quantity_val  = ($quantity > 0) ? $quantity : null;
    $currency_code = $data['currency_code'] ?? 'EUR';
    $barcode       = $data['barcode']       ?? '';
    $discount_per_val = !empty($data['discount_per']) ? floatval($data['discount_per']) : null;
    $discount_end_val = !empty($data['discount_end']) ? $data['discount_end'] : null;
    $admin_id      = intval($data['admin_id']);

    $old_stmt = $conn->prepare("SELECT * FROM products WHERE id_product = ?");
    $old_stmt->execute([$id]);
    $old       = $old_stmt->fetch();
    $price_old = $old['price'];
    $name_old  = $old['name'];

    try {
        $upd = $conn->prepare(
            "UPDATE products SET name=?, price=?, price_per_kg=?, currency_code=?, barcode=?,
             unit=?, quantity=?, discount_per=?, discount_end=?, last_price_change=NOW()
             WHERE id_product=?"
        );
        $upd->execute([$name, $price, $price_per_kg, $currency_code, $barcode,
                       $unit, $quantity_val, $discount_per_val, $discount_end_val, $id]);

        $changes = "";
        if ($price_old != $price) {
            $changes .= "Price: $price_old -> $price; ";
            $hist = $conn->prepare("INSERT INTO price_history (product_id, price, changed_at) VALUES (?, ?, NOW())");
            $hist->execute([$id, $price_old]);
        }
        if ($name_old != $name) {
            $changes .= "Product name: $name_old -> $name; ";
        }

        $disp = $conn->prepare("SELECT * FROM displays WHERE product_id = ?");
        $disp->execute([$id]);
        while ($row = $disp->fetch()) {
            if (empty($row['ip'])) continue;

            if ($discount_per_val !== null && $discount_end_val !== null && $discount_end_val > date('Y-m-d H:i:s')) {
                $dp      = $discount_per_val;
                $d_price = round($price * (1 - $dp / 100), 2);
                $lp      = $conn->prepare("SELECT MIN(price) AS lp FROM price_history WHERE product_id = ? AND changed_at >= NOW() - INTERVAL 30 DAY");
                $lp->execute([$id]);
                $h   = $lp->fetch();
                $low = ($h['lp'] !== null) ? min(floatval($h['lp']), $price) : $price;
                $pl_discount_per   = number_format($dp, 2, '.', '');
                $pl_discount_price = number_format($d_price, 2, '.', '');
                $pl_lowest_price   = number_format($low, 2, '.', '');
                $pl_discount_end   = $discount_end_val;
            } else {
                $pl_discount_per = $pl_discount_price = $pl_lowest_price = $pl_discount_end = null;
            }

            $payload = json_encode([
                "id_display"    => strval($row['id_display']),
                "id"            => strval($id),
                "name"          => $name,
                "price"         => number_format($price, 2, '.', ''),
                "price_per_kg"  => number_format($price_per_kg, 2, '.', ''),
                "unit"          => $unit,
                "quantity"      => $quantity > 0 ? number_format($quantity, 3, '.', '') : null,
                "barcode"       => $barcode,
                "updated"       => date('d-m-Y'),
                "discount_per"  => $pl_discount_per,
                "discount_price"=> $pl_discount_price,
                "lowest_price"  => $pl_lowest_price,
                "discount_end"  => $pl_discount_end ? date('d-m-Y', strtotime($pl_discount_end)) : null,
            ]);
            $ch = curl_init("http://" . $row['ip'] . "/update");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }

        if ($changes !== "") {
            $log = $conn->prepare(
                "INSERT INTO logs (id_log, admin_id, product_id, changed_at, what_changed)
                 VALUES (NULL, ?, ?, NOW(), ?)"
            );
            $log->execute([$admin_id, $id, $changes]);
        }

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id       = intval($data['id_product']);
    $admin_id = intval($data['admin_id']);

    $old_stmt = $conn->prepare("SELECT * FROM products WHERE id_product = ?");
    $old_stmt->execute([$id]);
    $old       = $old_stmt->fetch();
    $price_old = $old['price'];
    $name_old  = $old['name'];

    $disp = $conn->prepare("SELECT id_display, ip FROM displays WHERE product_id = ?");
    $disp->execute([$id]);
    while ($d = $disp->fetch()) {
        if (empty($d['ip'])) continue;
        $clear = json_encode(["id_display" => strval($d['id_display']), "clear" => true]);
        $ch = curl_init("http://" . $d['ip'] . "/update");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $clear);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    try {
        $log = $conn->prepare(
            "INSERT INTO logs (id_log, admin_id, product_id, changed_at, what_changed)
             VALUES (NULL, ?, ?, NOW(), ?)"
        );
        $log->execute([$admin_id, $id, "Deleted product $name_old with price: $price_old"]);

        $del = $conn->prepare("DELETE FROM products WHERE id_product = ?");
        $del->execute([$id]);

        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>
