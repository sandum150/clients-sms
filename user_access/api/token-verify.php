<?php
ini_set('display_errors', 1);


header('Access-Control-Allow-Origin', '*');
header('Content-Type: application/json');



require_once "../Database.php";
require_once "../JWT.php";
require_once "../../config.php";

$db = new Databease();

$conn = $db->connect();

try {
    $token = $_POST['token'];

    $decoded = JWT::decode($token, JWT_KEY, 'HS256');

    $query = "SELECT * FROM users WHERE login = '" . $decoded->login. "' AND status = 1";

    $stmt = $conn->prepare($query);

    $stmt->execute();
    if ($stmt->rowCount() > 0) {

        header("HTTP/1.1 200 OK");
        echo json_encode([
            'success' => true,
        ]);
        die;
    } else {
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "error" => "Token not valid"
        ]);
        die;
    }
} catch (PDOException $e) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
