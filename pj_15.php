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


$user_plan = [];
$trackes_by_users = [];

foreach ($trackers as $tracker) {
    if (!$tracker->clone && !$tracker->deleted) {
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
        $user_plan[$tracker->user_id]['disabled'] = false; //trackers are working?
        $user_plan[$tracker->user_id]['activated'] = $user_object->activated; // is allowed to log in

//        if at least one tracker is blocked
        if($tracker->source->blocked == true){
            $user_plan[$tracker->user_id]['disabled'] = true;
        }
    }

}


echo "Facem planul userilor fara trackere clonuri. \n";

echo "Obtinem lista alba de telefoane \n";

$report = [];
$conturi_plata = 0;
$forecast = 0;
foreach ($user_plan as $user_id => $user) {
    if ($user['type'] == 'legal_entity' && $user['activated']) { //legal_entity, individual
//        $report[$user_id]['sent_sms'] = false;
        $report[$user_id]['has_to_pay'] = $user['has_to_pay'];
        $report[$user_id]['balance'] = $user['balance'];
        $report[$user_id]['total_trackers'] = $user['total_trackers'];
        $report[$user_id]['tarif'] = $user['tarif'];
        $report[$user_id]['trackers'] = $user['trackers'];
        $report[$user_id]['mention'] = $user['mention'];
        $report[$user_id]['mention1'] = $user['mention1'];
        $report[$user_id]['sms_status'] = $daily->getSMSStatus($user_id);

        $balance_good = $user['has_to_pay'] > $user['balance'] ?  false : true;

        switch ($report[$user_id]['sms_status']){
            case 'ok':
                if($balance_good){
//                    $report[$user_id]['sms_action'] = ' - ';
                }else{
                    $conturi_plata++;
                    $report[$user_id]['sms_action'] = 'cont de plata';
                    $forecast += $report[$user_id]['has_to_pay'];
                }
                break;
            case 'sent':
                $daily->errorLog('invalid SMS status in pj.php. SMS deja a fost trimis. user id: ' . $user_id);
                $report[$user_id]['sms_action'] = 'status error (trimis)';
                break;

            case 'disabled':
                if($balance_good){
//                    $report[$user_id]['sms_action'] = 'achitat';
                    $daily->setSMSStaus($user_id, 'ok');
                }else{
//                    $report[$user_id]['sms_action'] = 'inactiv ' . $daily->getSMSDate($user_id);
                }
                break;

            case 'unknown':
                if ($balance_good){
//                    this is a new user, set status to ok. Welcome.
                    $daily->setSMSStaus($user_id, 'ok');
//                    $report[$user_id]['sms_action'] = ' - ';
                }else{
//                    new users also have to pay. Send SMS
                    $daily->sendSMS(SMS_MESSAGE_PF, $user['phone']);
                    $conturi_plata++;
                    $report[$user_id]['sms_action'] = 'cont de plata';
                    $forecast += $report[$user_id]['has_to_pay'];
                }
        }

    }
}


echo "Total conturi $conturi_plata \n";

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


                            <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF" style="table-layout: fixed">
                                <tr>
                                    <td align="left" valign="middle"
                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 24px; color: #353535; padding:3%; padding-top:40px; padding-bottom:40px;">
                                        <?php echo MAIL_SUBJECT_PJ_1; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <table width="94%" border="0" cellpadding="0" cellspacing="0" style="table-layout: fixed">
                                            <tr>
                                                <td align="left" bgcolor="#2B0057"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0; width: 10%">
                                                    Client ID
                                                </td>
                                                <td align="left" bgcolor="#2B0057"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0; width: 12%">
                                                    Balanta | Tarif lunar
                                                </td>
                                                <td align="left" bgcolor="#2B0057"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0; width: 25%">
                                                    Obiecte x Tarif lunar
                                                </td>
                                                <td align="left" bgcolor="#2B0057"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0; width: 20%">
                                                    Mentiuni
                                                </td>
                                                <td align="left" bgcolor="#2B0057"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; white-space: nowrap; width: 20%">
                                                    Mentiuni 1
                                                </td>
                                                <td align="right" bgcolor="#2B0057"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-left:0; width: 13%">
                                                    Statut
                                                </td>
                                            </tr>
                                            <?php

                                            foreach ($report as $user_id => $user):
                                                if($user['sms_action']): ?>
                                                <tr>
                                                    <td  align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $user_id; ?>
                                                    </td>
                                                    <td align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $user['balance'] . " | " . $user['has_to_pay']; ?>
                                                    </td>
                                                    <td align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php
                                                        $str = '';
                                                        foreach ($user['trackers'] as $tarif_id => $count) {
                                                            $str .= '(' . $count . ' x ' . $tarrifs[$tarif_id] . ') + ';
                                                        }
                                                        echo rtrim($str, '+ ') . ' = ' . $user['has_to_pay']; ?>
                                                    </td>
                                                    <td align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px;">
                                                        <?php echo $user['mention']; ?>
                                                    </td>
                                                    <td align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $user['mention1']; ?>
                                                    </td>
                                                    <td align="right" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; padding:10px; padding-left:0;">
                                                        <?php echo $user['sms_action']
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php endif;
                                            endforeach; ?>
                                            <tr>
                                                <td colspan="6">
                                                    <table width="100%">
                                                        <tr>
                                                            <td width="20%" align="right" bgcolor="#FFFFFF"
                                                                style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;"></td>
                                                            <td width="20%" align="right" bgcolor="#FFFFFF"
                                                                style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;"></td>
                                                            <td width="20%" align="right" bgcolor="#FFFFFF"
                                                                style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;"></td>
                                                            <td width="20%" align="right" bgcolor="#FFFFFF"
                                                                style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;">
                                                                <b>Total conturi de plata</b>
                                                            </td>
                                                            <td width="20%" align="right" bgcolor="#FFFFFF"
                                                                style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;">
                                                                <b><?php echo $conturi_plata; ?></b>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td width="20%" align="right" bgcolor="#FFFFFF"
                                                                style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;"></td>
                                                            <td width="20%" align="right" bgcolor="#FFFFFF"
                                                                style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;"></td>
                                                            <td width="20%" align="right" bgcolor="#FFFFFF"
                                                                style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;">
                                                            <td width="30%" align="right" bgcolor="#FFFFFF"
                                                                style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;">
                                                                <b>Suma conturilor de plata</b>
                                                            </td>
                                                            <td width="20%" align="right" bgcolor="#FFFFFF"
                                                                style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;">
                                                                <b><?php echo $forecast; ?></b>
                                                            </td>
                                                        </tr>
                                                    </table>
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
//$mail->addAddress('sandum150@gmail.com');               // Name is optional
$mail->addReplyTo(MAIL_REPLY_TO, 'Information');
//$mail->addCC('cc@example.com');
//$mail->addBCC('bcc@example.com');

//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
$mail->isHTML(true);                                  // Set email format to HTML

$mail->Subject = MAIL_SUBJECT_PJ_1;
$mail->Body = $mail_body;
$mail->AltBody = 'Raport PJ pentru cont de plata: ' . $conturi_plata;

if(!MAIL_TEST_MODE){
    if (!$mail->send()) {
        echo "Email nu a putut fi trimis. \n";
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        $daily->errorLog('Mail could not be sent.');
    } else {
        echo "Email cu raport a fost trimis \n";
    }
}else{
    echo $mail_body;
}