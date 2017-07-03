<?php
require_once 'config.php';

class ClientChecker{
    function __construct() {
        $this->hash = $this->getHash();

        $white_list = file_get_contents('white_list.txt');
        $this->white_phones = explode("\n", $white_list);

        $this->conn = new mysqli(DB_SERVER_NAME, DB_USER_NAME, DB_PASSWORD, DB_DB_NAME);
        if ($this->conn->connect_error) {
            $this->errorLog("Connection to db failed : " . $this->conn->connect_error);
        }
    }

    function __destruct(){
        $this->conn->close();
    }

    private $admin_dashboard_user = NX_DASHBOARD_USERNAME;
    private $admin_dashboard_pass = NX_DASHBOARD_PASSWORD;
    private $admin_dashboard_api_url = NX_DASHBOARD_ADMIN_URL;
    private $limit_amount = 30;
    private $hash;
    public $white_phones;
    public $conn;

//    SMS

    private $sms_user = SMS_API_USERNAME;
    private $sms_pass = SMS_API_PASSWORD;

    private function getHash(){
        $file_hash = file_get_contents("hash.txt");
//        check the hash
        if($this->isValidHash($file_hash)){
            return $file_hash;
        }else{
            $this->errorLog("Hash is not valid. Going to get a new one...");
            $result = $this->curlRequest($this->admin_dashboard_api_url."/account/auth/", array(
                'login'     => urlencode($this->admin_dashboard_user),
                'password'  => urlencode($this->admin_dashboard_pass)
            ));

            if($result){
                $result = json_decode($result);
                if($result->success == true){
                    if(!file_put_contents("hash.txt", $result->hash)) $this->errorLog("Could not save hash to file");
                    return $result->hash;
                }else{
                    $this->errorLog("Something went wrong on login to API");
                    return false;
                }
            }else{
                $this->errorLog("Could not connect to API server. Curl error.");
                return false;
            }
        }

    }

    public function curlRequest($url, $args = null){
        $parameters = '';
        if($args){
            if(array_key_exists('hash', $args) && $args['check_validity'] == true){
                $parameters .= "&hash=".$args['hash'];
                unset($args['hash']);
                unset($args['check_validity']);
            }else{
                $parameters .= "&hash=".$this->hash;
            }
        }else{
            $parameters .= "&hash=".$this->hash;
        }
        if($args){
            foreach ($args as $key => $arg){
                $parameters .= "&$key=$arg";
            }
        }
        $parameters = ltrim($parameters , "&");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if($parameters){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec ($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
        curl_close ($ch);
        if(!$result){
            $this->errorLog("Could not make a curl request", $status_code);
            return false;
        }else{
            return $result;
        }
    }

    public function isValidHash($hash){
        $request = $this->curlRequest($this->admin_dashboard_api_url."user/list/", ['hash' => $hash, 'check_validity' => true]);
        $result = json_decode($request);
        return $result->success;
    }

    public function getUsersList(){
        $result = $this->curlRequest($this->admin_dashboard_api_url."user/list/");
        $users = json_decode($result);
        return $users->list;
    }

    public function getTrackerList(){
        $trackers = $this->curlRequest($this->admin_dashboard_api_url."tracker/list/");
        $trackers = json_decode($trackers);
        return $trackers->list;
    }

    public function getTariffList(){
        $tariffs = $this->curlRequest($this->admin_dashboard_api_url."tariff/list/");
        $tariffs = json_decode($tariffs);
        $tarrif_list = [];
        foreach ($tariffs->list as $tarrif){
            $tarrif_list[$tarrif->id] = $tarrif->price;
        }
        return $tarrif_list;
    }

    public function sendSMS($message, $recipient){

        if(!SMS_TEST_MODE){
            $query = 'http://'.SMS_SERVER_IP_PORT.'/cgi/WebCGI?1500101=account='.$this->sms_user.'&password='.$this->sms_pass.'&port=1&destination='.$recipient.'&content='.urlencode($message);
            $response = file_get_contents($query);

            if(!strpos($response, "Success")){
                $response = explode("\n", $response);
                $this->errorLog($response[2], 'sendSMS '.$recipient);
                return false;
            }
        }

        return true;

    }

    public function errorLog( $error, $code = null){
        $file = 'errors.log';

        $current = file_get_contents($file);

        $current .= date("Y-m-d H:i:s ");

        if($code){
            $current .= " Code:" .$code . " ";
        }

        if($error){
            $current .= "Error message: " . $error . "\n";
        }else{
            $current .= "Unknown error \n";
        }
        if(!file_put_contents($file, $current)){
            die("Could not write to $file");
        }


    }

    /**
     * @return string
     */
    public function isUserPhoneWhiteListed($user)    {
//        return $this->sms_pass;
        return in_array($user['phone'], $this->white_phones);
    }


    public function getSMSStatus($user_id){
        //        $sms_status = 'sent'; //sent | ok | unknown
        $sql = "SELECT status FROM sms_status WHERE user_id = $user_id";
        $result = $this->conn->query($sql);
        $sms_status = 'unknown';
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $sms_status = $row['status'];
            }
        }
        return $sms_status;
    }


    public function setSMSStaus($user_id, $status){
        $sql = "INSERT INTO sms_status (user_id, status) 
                VALUES ($user_id, '$status') 
                ON DUPLICATE KEY UPDATE 
                status = '$status';";
        if ($this->conn->query($sql) === TRUE) {
            return true;
        } else {
            $this->errorLog("nu a putut fi setat sms_status pentru user $user_id ");
            return false;
        }
    }


// * @TODO get SMS sent date
    public function getSMSDate($user_id){
        $sql = "SELECT time FROM sms_status WHERE user_id = $user_id AND (status = 'sent' OR status = 'disabled')" ;
//        return $sql;
        $result = $this->conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {

                $status_time = strtotime($row['time']);


                $now = time(); // or your date as well
                $datediff = $now - $status_time;

                return floor($datediff / (60 * 60 * 24)) . ' zile';



            }
        }
//        return $sms_status;
    }


}

function sortByStatus($a, $b){
    return $b['sms_action'] < $a['sms_action'];
}
