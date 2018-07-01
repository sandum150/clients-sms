<?php
//phpinfo();
//ini_set('display_errors', 1);

require_once "../Database.php";
require_once "../JWT.php";
require_once "../../config.php";
require_once "../../ClientChecker.php";
require_once "../functions.php";


$clientChecker = new ClientChecker();


$db = new Databease();

$conn = $db->connect();

header('Content-Type: application/json');
cors();

try {
    $headers = apache_request_headers();
    $token = $headers['Authorization'];

//    echo $token; die;

    $decoded = JWT::decode($token, JWT_KEY, 'HS256');

    $query = "SELECT * FROM users WHERE login = '" . $decoded->login . "' AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();


    if ($stmt->rowCount() > 0) {

        $allUsers = $clientChecker->getUsersList();

        $json = json_decode(file_get_contents('php://input'));

        $searchKey = $json->search;

        $foundUserId = null;

//        if search string is empty don't search
        if (trim($searchKey)== ""){
            header("HTTP/1.1 400 Bad Request");
            echo json_encode([
                "success" => false,
                "error" => "Search string should not be empty."
            ]);
            die;
        }

        foreach ($allUsers as $user) {
            if ($user->login == $searchKey || $user->phone == $searchKey || $user->id == $searchKey) {
                $foundUserId = $user->id;
                break;
            }
        }

        $data = [];

        $data['user_id'] = $foundUserId;

//        echo "User: ".$foundUserId;

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
                "message" => "User not found",
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
