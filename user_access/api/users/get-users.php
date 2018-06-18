<?php
//phpinfo();
//ini_set('display_errors', 1);

require_once "../../Database.php";
require_once "../../JWT.php";
require_once "../../../config.php";
require_once "../../../ClientChecker.php";
require_once "../../functions.php";


$clientChecker = new ClientChecker();


$db = new Databease();

$conn = $db->connect();

header('Content-Type: application/json');
cors();



try {
    $headers = apache_request_headers();
    $token = $headers['Authorization'];
//    echo json_encode($headers);
//    $token = $_GET['token'];

//    echo $token; die;

    $decoded = JWT::decode($token, JWT_KEY, 'HS256');
//    echo $decoded, die;
    $query = "SELECT * FROM users WHERE login = '" . $decoded->login . "' AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();


    if ($stmt->rowCount() > 0) {

        $query = "SELECT * FROM users";
        $stmt = $conn->prepare($query);
        $stmt->execute();


        $result = [];
        $result['users'] = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $user = array(
                'id' => $id,
                'login' => $login,
                'access_level' => $access_level,
                'status' => $status,
                'email' => $email,
            );
            // Push to "data"
//            $result['users'][] = $user;
            array_push($result['users'], $user);
        }

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
