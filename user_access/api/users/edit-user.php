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


    $decoded = JWT::decode($token, JWT_KEY, 'HS256');
//    echo $decoded, die;
    $query = "SELECT * FROM users WHERE login = '" . $decoded->login . "' AND status = 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {

        $json = json_decode(file_get_contents('php://input'));

//        if request method is post
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($json->user_id)) {
            $query = '';
            switch ($json->action){
                case 'chpass':
                    $query = "UPDATE users SET password = '".JWT::hashPassword($json->new_password, PASSWORD_KEY)."' WHERE id = $json->user_id";
                    break;
                case 'disable':
                    $query = "UPDATE users SET status = 0 WHERE id = $json->user_id";
                    break;
                case 'enable':
                    $query = "UPDATE users SET status = 1 WHERE id = $json->user_id";
                    break;
                default:
                    break;
            }


//            execute the change
            if($conn->query($query)){
                header("HTTP/1.1 200 OK");
                echo json_encode([
                    "success" => true,
                    "message" => 'User updated successfully'
                ]);
                die;
            }else{
                header("HTTP/1.1 500");
                echo json_encode([
                    "success" => false,
                    "message" => 'Could not update the user'
                ]);
                die;
            }

        }



        /*$query = "SELECT * FROM users";
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
            array_push($result['users'], $user);
        }*/

        header("HTTP/1.1 200 OK");
        echo json_encode([
            "json" => $json,
            "method" => $_SERVER['REQUEST_METHOD']
        ]);



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
