<?php
//phpinfo();
//ini_set('display_errors', 1);

header('Access-Control-Allow-Origin', '*');
header('Content-Type: application/json');

require_once "../Database.php";
require_once "../JWT.php";
require_once "../../config.php";
require_once "../../ClientChecker.php";


$clientChecker = new ClientChecker();


$db = new Databease();

$conn = $db->connect();

try {
    $token = $_POST['token'];

    $decoded = JWT::decode($token, JWT_KEY, 'HS256');

    $query = "SELECT * FROM users WHERE login = '" . $decoded->login . "' AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();


    if ($stmt->rowCount() > 0) {

        $allUsers = $clientChecker->getUsersList();
        $searchKey = $_POST['search'];

        $foundUserId = null;

        foreach ($allUsers as $user) {
            if ($user->login == $searchKey || $user->phone == $searchKey) {
                $foundUserId = $user->id;
                break;
            }
        }

        $data = [];

        $data['user_id'] = $foundUserId;

        if ($foundUserId) {
            $userHash = $clientChecker->getUserSession($foundUserId);

            header("HTTP/1.1 200 OK");
            echo json_encode([
                "success" => true,
                "user_hash" => $userHash
            ]);
        } else {
            header("HTTP/1.1 400 Bad Request");
            echo json_encode([
                "success" => false,
            ]);
        }


    } else {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode([
            "success" => false,
            "error" => "Something went wrong"
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
