<?php

//require_once "../config.php";
require_once("../../config.php");
class Databease {

    private $conn;

    public function connect(){
        $this->conn = null;

        try{
            $this->conn = new PDO('mysql:host=' . DB_SERVER_NAME .';dbname=' . DB_DB_NAME, DB_USER_NAME, DB_PASSWORD );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch (PDOException $e) {
            echo "Connection error. Message: $e->getMessage()";
        }

        return $this->conn;
    }

}