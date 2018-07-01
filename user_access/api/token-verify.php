<?php
//ini_set('display_errors', 1);

require_once __DIR__."/../Database.php";
require_once __DIR__."/../JWT.php";
require_once __DIR__."/../../config.php";
require_once __DIR__."/../functions.php";
require_once __DIR__."/../../ClientChecker.php";

cors();

$db = new Databease();

$cc = new ClientChecker();

$conn = $db->connect();

try {
    $headers = apache_request_headers();

    $token = $headers['Authorization'];

    $decoded = JWT::decode($token, JWT_KEY, 'HS256');

    $query = "SELECT * FROM users WHERE login = '" . $decoded->login. "' AND status = 1";

    $stmt = $conn->prepare($query);

    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $result = $stmt->fetch();
        $user = [];
        $user['login'] = $result['login'];
        $user['email'] = $result['email'];
        $user['access_level'] = $result['access_level'];
        $user['status'] = $result['status'];


//        get trackers
        $trackers = $cc->getTrackerList();
        $users = $cc->getUsersList();
        $tarifs = $cc->getTariffList(true);

        header("HTTP/1.1 200 OK");
        echo json_encode([
            'success' => true,
            'user' => $user,
            'trackers' => $trackers,
            'users' => $users,
            'tarifs' => $tarifs
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
