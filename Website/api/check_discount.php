<?php
include 'db.php';

$stmt = $conn->query("SELECT * FROM products WHERE discount_end IS NOT NULL AND discount_end < NOW()");
while ($row = $stmt->fetch()) {
    $id = $row['id_product'];

    $upd = $conn->prepare("UPDATE products SET discount_per = NULL, discount_end = NULL WHERE id_product = ?");
    $upd->execute([$id]);

    $log = $conn->prepare("INSERT INTO logs (admin_id, product_id, what_changed) VALUES (0, ?, 'Discount expired automatically')");
    $log->execute([$id]);

    $disp = $conn->prepare("SELECT * FROM displays WHERE product_id = ?");
    $disp->execute([$id]);
    while ($display = $disp->fetch()) {
        if (empty($display['ip'])) continue;
        $payload = json_encode([
            "id_display"    => strval($display['id_display']),
            "id"            => strval($row['id_product']),
            "name"          => $row['name'],
            "price"         => number_format(floatval($row['price']), 2, '.', ''),
            "price_per_kg"  => number_format(floatval($row['price_per_kg']), 2, '.', ''),
            "unit"          => $row['unit'],
            "quantity"      => $row['quantity'] !== null ? number_format(floatval($row['quantity']), 3, '.', '') : null,
            "barcode"       => $row['barcode'],
            "updated"       => !empty($row['last_price_change']) ? date('d-m-Y', strtotime($row['last_price_change'])) : null,
            "discount_per"  => null,
            "discount_price"=> null,
            "lowest_price"  => null,
            "discount_end"  => null,
        ]);
        $ch = curl_init("http://" . $display['ip'] . "/update");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
    }
}
?>
