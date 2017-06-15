<?php
/**
 * Created by PhpStorm.
 * User: sandu
 * Date: 5/14/17
 * Time: 5:38 PM
 */

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
error_reporting(0);

require_once ("ClientChecker.php");

$checker = new ClientChecker();

//$users = $checker->getUsersList();

//$trackers = $checker->curlRequest("https://api.navixy.com/v2/panel/tracker/list/", array(
////    'hash' => $checker->hash
//));

//$hash = $checker->isValidHash('b051fc34dda0082dc54ce9c36e315339');

//$tariffs = $checker->curlRequest("https://api.navixy.com/v2/panel/tariff/list/", array(
//    'hash' => $checker->hash
//));

$white_list = file_get_contents('white_list.txt');

$white_phones = explode("\n", $white_list);


$status = $checker->getSMSStatus(555);

$new_status = $checker->setSMSStaus(100, 'unknown');



echo '<pre>';
var_dump($new_status);
echo '</pre>';

//data
//echo '<pre>';
//echo "<b>TARIFFS</b>\n";
//var_dump($checker->getTariffList());
//echo "<b>TRACKERS</b>\n";
//var_dump($checker->getTrackerList());
//echo "<b>USERS</b>\n";
//var_dump($users);
//echo '</pre>';