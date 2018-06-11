<?php
ini_set('display_errors', 1);


header('Access-Control-Allow-Origin', '*');
header('Content-Type', 'application/json');


require_once "../Database.php";
require_once "../JWT.php";
require_once "../../config.php";

$db = new Databease();

$conn = $db->connect();

try {
    $query = "SELECT * FROM users WHERE login = '" . $_POST['login'] . "' AND password = '" . JWT::hashPassword($_POST['password'], PASSWORD_KEY) . "' AND status = 1";

    $stmt = $conn->prepare($query);

    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $result = $stmt->fetch();
        $user = [];
        $user['login'] = $result['login'];
        $user['email'] = $result['email'];
        $user['access_level'] = $result['access_level'];
        $user['status'] = $result['status'];

        header("HTTP/1.1 200 OK");

        echo json_encode([
            'success' => true,
            'user' => $user,
            'token' => JWT::encode($user, JWT_KEY)
        ]);
        die;
    } else {
        header("HTTP/1.1 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "error" => "User of password not correct"
        ]);
    }
} catch (PDOException $e) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
