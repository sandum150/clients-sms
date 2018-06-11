<?php
require_once "JWT.php";
require_once "Databease.php";
require_once "../config.php";

$data = [
    "login" => "jora",
    "email" => "jora@tehos.md"
];

$token = JWT::encode($data, JWT_KEY);

echo $token;

$str = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJsb2dpbiI6ImpvcmEiLCJlbWFpbCI6ImpvcmFAdGVob3MubWQifQ.OUWraKL5JVJ5RCsXAbKFwJDVXX9665aQ8sZLJ_baDa0';

$decoded = JWT::decode($str, JWT_KEY, 'HS256');
echo '<pre>';
var_dump($decoded);
echo '</pre>';