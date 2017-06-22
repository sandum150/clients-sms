<?php
error_reporting(0);
require('ClientChecker.php');
require_once 'PHPMailer-master/PHPMailerAutoload.php';

$daily = new ClientChecker();

$tarrifs = $daily->getTariffList();
echo "Lista de tarife a fost obtinuta \n";
$trackers = $daily->getTrackerList();
echo "Lista de trackere a fost obtinuta \n";
$users = $daily->getUsersList();
echo "Lista de useri a fost obtinuta \n";


$number_of_days_in_month = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));


$user_plan = [];
$trackes_by_users = [];

foreach ($trackers as $tracker) {
    if (!$tracker->clone) {
        $tracker_price = $tarrifs[$tracker->source->tariff_id];
//    echo "tracker id: " . $tracker->tariff_id . "<br>";

//        we need to know how many trackers for each tarif does have users
        if (isset($trackes_by_users[$tracker->user_id][$tracker->source->tariff_id])) {
            $trackes_by_users[$tracker->user_id][$tracker->source->tariff_id]++;
        } else {
            $trackes_by_users[$tracker->user_id][$tracker->source->tariff_id] = 1;
        }


// identify the user of the tracker
        $user_object = null;
        foreach ($users as $user) {
            if ($user->id == $tracker->user_id) {
                $user_object = $user;
                break;
            }
        }

        $user_plan[$tracker->user_id]['has_to_pay'] += $tracker_price;
        $user_plan[$tracker->user_id]['type'] = $user_object->legal_type;
        $user_plan[$tracker->user_id]['balance'] = $user_object->balance;
        $user_plan[$tracker->user_id]['total_trackers'] += 1;
        $user_plan[$tracker->user_id]['phone'] = substr($user_object->phone, -8);
        $user_plan[$tracker->user_id]['tarif'] = $tracker_price;
        $user_plan[$tracker->user_id]['trackers'] = $trackes_by_users[$tracker->user_id];
        $user_plan[$tracker->user_id]['mention'] = $user_object->post_city;
        $user_plan[$tracker->user_id]['mention1'] = $user_object->registered_city;

    }

}


echo "Facem planul userilor fara trackere clonuri. \n";

echo "Obtinem lista alba de telefoane \n";

$report = [];
$sent_sms = 0;
foreach ($user_plan as $user_id => $user) {
    if ($user['type'] == 'legal_entity') { //legal_entity, individual
//        $report[$user_id]['sent_sms'] = false;
        $report[$user_id]['has_to_pay'] = $user['has_to_pay'];
        $report[$user_id]['balance'] = $user['balance'];
        $report[$user_id]['total_trackers'] = $user['total_trackers'];
        $report[$user_id]['tarif'] = $user['tarif'];
        $report[$user_id]['trackers'] = $user['trackers'];
        $report[$user_id]['mention'] = $user['mention'];
        $report[$user_id]['mention1'] = $user['mention1'];
        $report[$user_id]['sms_status'] = $daily->getSMSStatus($user_id);

//        $number_of_days_in_month


        $balance_good = $user['has_to_pay'] > $user['balance'] ?  false : true;

        switch ($report[$user_id]['sms_status']){
            case 'ok':
                $report[$user_id]['sms_action'] = ' - ';
                break;
            case 'sent':
                if($balance_good){
//                    set staus to ok
                    $daily->setSMSStaus($user_id, 'ok');
                    $report[$user_id]['sms_action'] = 'achitat';
                }else{
//                    nothing to do, sms was already sent last days
                    $daily->sendSMS(SMS_NEPLATA_PJ_DATA_1, $user_id);
                    $report[$user_id]['sms_action'] = 'dezactivat';
                    $daily->setSMSStaus($user_id, 'disabled');
                    $sent_sms++;
                }
                break;
            case 'disabled':
                if($balance_good){
//                    set staus to ok
                    $daily->setSMSStaus($user_id, 'ok');
                    $report[$user_id]['sms_action'] = 'achitat';
                }else{
//                    nothing to do, sms was already sent last days
                    $report[$user_id]['sms_action'] = 'inactiv' . $daily->getSMSDate($user_id);
                }
                break;

            case 'unknown':
                if ($balance_good){
//                    this is a new user, set status to ok. Welcome.
                    $daily->setSMSStaus($user_id, 'ok');
                    $report[$user_id]['sms_action'] = ' - ';
                }else{
//                    new users also have to pay. Send SMS and set status to sent
                    $daily->sendSMS(SMS_NEPLATA_PJ_DATA_1, $user['phone']);
                    $sent_sms++;
                    $daily->setSMSStaus($user_id, 'disabled');
                    $report[$user_id]['sms_action'] = 'dezactivat';
                }
        }

    }
}


echo "Total SMS trimise $sent_sms \n";

//sorting the report by sms_action column
uasort($report, 'sortByStatus');

ob_start(); ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title></title>
        <style type="text/css">
            #outlook a {
                padding: 0;
            }

            body {
                width: 100% !important;
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
                margin: 0;
                padding: 0;
            }

            /* force default font sizes */
            .ExternalClass {
                width: 100%;
            }

            .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {
                line-height: 100%;
            }

            /* Hotmail */
            table td {
                border-collapse: collapse;
            }

            @media only screen and (min-width: 700px) {
                .maxW {
                    width: 700px !important;
                }
            }
        </style>
    </head>
    <body style="margin: 0px; padding: 0px; -webkit-text-size-adjust:none; -ms-text-size-adjust:none;" leftmargin="0"
          topmargin="0" marginwidth="0" marginheight="0" bgcolor="#FFFFFF">
    <table bgcolor="#CCCCCC" width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr>
            <td valign="top">
                <!--[if (gte mso 9)|(IE)]>

                <table width="700" align="center" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td valign="top">
                <![endif]-->
                <table width="100%" class="maxW" style="max-width: 700px; margin: auto;" border="0" align="center"
                       cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" align="center">


                            <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF">
                                <tr>
                                    <td align="left" valign="middle"
                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 24px; color: #353535; padding:3%; padding-top:40px; padding-bottom:40px;">
                                        Raport lunar persoane juridice dezactivare serciciu
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <table width="94%" border="0" cellpadding="0" cellspacing="0" style="table-layout: fixed">
                                            <tr>
                                                <td width="10%" align="left" bgcolor="#4AA908"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Client ID
                                                </td>
                                                <td width="15%" align="left" bgcolor="#4AA908"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Balanta
                                                </td>
                                                <td width="25%" align="left" bgcolor="#4AA908"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Trackere
                                                </td>
                                                <td width="18%" align="left" bgcolor="#4AA908"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Mentiuni
                                                </td>
                                                <td width="17%" align="left" bgcolor="#4AA908"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; white-space: nowrap">
                                                    Mentiuni 1
                                                </td>
                                                <td width="15%" align="right" bgcolor="#4AA908"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-left:0;">
                                                    SMS trimis
                                                </td>
                                            </tr>
                                            <?php

                                            foreach ($report as $user_id => $user): ?>
                                                <tr>
                                                    <td width="10%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $user_id; ?>
                                                    </td>
                                                    <td width="10%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $user['balance']; ?>
                                                    </td>
                                                    <td width="30%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php
                                                        $str = '';
                                                        foreach ($user['trackers'] as $tarif_id => $count) {
                                                            $str .= '(' . $count . ' x ' . $tarrifs[$tarif_id] . ') + ';
                                                        }
                                                        echo rtrim($str, '+ ') . ' = ' . $user['has_to_pay']; ?>
                                                    </td>
                                                    <td width="20%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px;">
                                                        <?php echo $user['mention']; ?>
                                                    </td>
                                                    <td width="20%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $user['mention1']; ?>
                                                    </td>
                                                    <td width="10%" align="right" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; padding:10px; padding-left:0;">
                                                        <?php echo $user['sms_action']
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            endforeach; ?>
                                            <tr>
                                                <td width="20%" align="right" bgcolor="#FFFFFF"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;"></td>
                                                <td width="20%" align="right" bgcolor="#FFFFFF"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;"></td>
                                                <td width="20%" align="right" bgcolor="#FFFFFF"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;"></td>
                                                <td width="20%" align="right" bgcolor="#FFFFFF"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;">
                                                <td width="20%" align="right" bgcolor="#FFFFFF"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;">
                                                    <b>Total SMS</b>
                                                </td>
                                                <td width="20%" align="right" bgcolor="#FFFFFF"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;">
                                                    <b><?php echo $sent_sms; ?></b>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="middle"
                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 14px; color: #353535; padding:3%; padding-top:40px; padding-bottom:40px;">
                                        Raport generat la : <?php echo date('d F Y, H:i:s') ?>
                                        <!-- using &nbsp; will prevent orphan words -->
                                    </td>
                                </tr>
                            </table>


                        </td>
                    </tr>
                </table>
                <!--[if (gte mso 9)|(IE)]>
                </td></tr></table>
                <![endif]-->
            </td>
        </tr>
    </table>
    </body>
    </html>
<?php
$mail_body = ob_get_contents();
ob_clean();


// SENDING THE MAIL WITH REPORT


$mail = new PHPMailer;

//$mail->SMTPDebug = 3;                               // Enable verbose debug output

$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = SMTP_SERVER;  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = SMTP_USERNAME;                 // SMTP username
$mail->Password = SMTP_PASSWORD;                           // SMTP password
$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = SMTP_PORT;                                    // TCP port to connect to

$mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
$mail->addAddress(MAIL_TO);     // Add a recipient
//$mail->addAddress('ellen@example.com');               // Name is optional
$mail->addReplyTo(MAIL_REPLY_TO, 'Information');
//$mail->addCC('cc@example.com');
//$mail->addBCC('bcc@example.com');

//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
$mail->isHTML(true);                                  // Set email format to HTML

$mail->Subject = MAIL_SUBJECT_PJ_2;
$mail->Body = $mail_body;
$mail->AltBody = 'Total SMS trimise catre persoane juridice: ' . $sent_sms;


if (!MAIL_TEST_MODE) {
    if (!$mail->send()) {
        echo "Email nu a putut fi trimis. \n";
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        $daily->errorLog('Mail could not be sent.');
    } else {
        echo "Email cu raport a fost trimis \n";
    }
} else {
    echo $mail_body;
}