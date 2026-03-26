<?php
include 'db.php'; //Ukljucuje file koji je povezan sa bazom
 header("Content-Type: application/json"); // nemam blage iskreno, nagdajam da postavllja nekakav header  za slanje jsona
 $raw = file_get_contents("php://input");
 $data = json_decode($raw, true);
 $username = $data["username"];
 $password = $data["password"];
 $sql = "SELECT * FROM admins WHERE username = '$username' AND password = '$password'"; //sql upit za pvorjeru u bazi
 $result = $conn->query($sql); //rezultat upita cijeli row
 if($result->num_rows > 0) {
    $row = $result->fetch_assoc(); // ako postoji redak salje ga kao asocijativni row
    echo json_encode(["success" => true, "admin" => $row]); //salje json sa porukom
    } else {
        echo json_encode(["success" => false, "message" => "Invalid credentials"]); //salje json sa porukom
    }
 ?>