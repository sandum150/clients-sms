<?php

require_once "../../Database.php";
require_once "../../JWT.php";
require_once "../../../config.php";
require_once "../../../ClientChecker.php";
require_once "../../functions.php";

$cc = new ClientChecker();


header('Content-Type: application/json');
cors();

try {
    $headers = apache_request_headers();
    $token = $headers['Authorization'];


    $decoded = JWT::decode($token, JWT_KEY, 'HS256');

    $query = "SELECT * FROM users WHERE login = '" . $decoded->login . "' AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {


        $result = [];

        $cc->curlRequest();

        header("HTTP/1.1 200 OK");
        echo json_encode($result);



    } else {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode([
            "success" => false,
            "error" => "Something went wrong",
            "token" => $decoded
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
