<?php

require ('ClientChecker.php');
require_once 'PHPMailer-master/PHPMailerAutoload.php';

$daily = new ClientChecker();

$tarrifs = $daily->getTariffList();
$trackers = $daily->getTrackerList();
$users = $daily->getUsersList();

//echo '<pre>';
//var_dump($trackers);
//var_dump($tarrifs);
//var_dump($users);
//echo '</pre>';
$user_plan = [];

foreach ($trackers as $tracker){
    if(!$tracker->clone){
        $tracker_price = $tarrifs[$tracker->source->tariff_id];
//    echo "tracker id: " . $tracker->tariff_id . "<br>";


// identify the user of the tracker
        $user_object = null;
        foreach ($users as $user){
            if($user->id == $tracker->user_id){
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
    }

}

//echo '<pre>';
//var_dump($user_plan);
//echo '</pre>';

$white_list = file_get_contents('white_list.txt');
$white_phones = explode("\n", $white_list);

$report = [];
$sent_sms = 0;
foreach ($user_plan as $user_id => $user){
    if($user['type'] == 'legal_entity'){ //legal_entity, individual
        $report[$user_id]['sent_sms'] = false;
        $report[$user_id]['has_to_pay'] = $user['has_to_pay'];
        $report[$user_id]['balance'] = $user['balance'];
        $report[$user_id]['total_trackers'] = $user['total_trackers'];
        $report[$user_id]['tarif'] = $user['tarif'];
        if($user['has_to_pay'] > $user['balance']){
            if(in_array($user['phone'], $white_phones)){
                $report[$user_id]['white_list'] = true;
                $report[$user_id]['sent_sms'] = true;
            }else{
                $report[$user_id]['sent_sms'] = $daily->sendSMS(SMS_MESSAGE_PF, $user['phone']);
            }
            $sent_sms++;
//            $report[$user_id]['sent_sms'] = true;

        }
    }
}

echo 'report: <br>';
echo '<pre>';
var_dump($report);
echo '</pre>';
echo $sent_sms;



ob_start(); ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title></title>
        <style type="text/css">
            #outlook a {padding:0;}
            body{width:100% !important; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; margin:0; padding:0;} /* force default font sizes */
            .ExternalClass {width:100%;} .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {line-height: 100%;} /* Hotmail */
            table td {border-collapse: collapse;}
            @media only screen and (min-width: 600px) { .maxW { width:600px !important; } }
        </style>
    </head>
    <body style="margin: 0px; padding: 0px; -webkit-text-size-adjust:none; -ms-text-size-adjust:none;" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" bgcolor="#FFFFFF"><table bgcolor="#CCCCCC" width="100%" border="0" align="center" cellpadding="0" cellspacing="0"><tr><td valign="top">
                <!--[if (gte mso 9)|(IE)]>

                <table width="600" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td valign="top">
                <![endif]-->
                <table width="100%" class="maxW" style="max-width: 600px; margin: auto;" border="0" align="center" cellpadding="0" cellspacing="0"><tr><td valign="top" align="center">


                            <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF">
                                <tr>
                                    <td align="left" valign="middle" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 24px; color: #0C4C69; padding:3%; padding-top:40px; padding-bottom:40px;">
                                        Raport lunar persoane juridice restante
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <table width="94%" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td width="20%" align="left" bgcolor="#8A0E00" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Client ID
                                                </td>
                                                <td width="20%" align="left" bgcolor="#8A0E00" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Balanta / debit
                                                </td>
                                                <td width="20%" align="left" bgcolor="#8A0E00" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Total trackere
                                                </td>
                                                <td width="20%" align="left" bgcolor="#8A0E00" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Tarif
                                                </td>
                                                <td width="20%" align="right" bgcolor="#8A0E00" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-left:0;">
                                                    SMS trimis
                                                </td>
                                            </tr>
                                            <?php

                                            foreach ($report as $user_id => $user): ?>
                                                <tr>
                                                    <td width="20%" align="left" bgcolor="#FFFFFF" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $user_id; ?>
                                                    </td>
                                                    <td width="20%" align="left" bgcolor="#FFFFFF" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $user['balance'].' / '.$user['has_to_pay']; ?>
                                                    </td>
                                                    <td width="20%" align="left" bgcolor="#FFFFFF" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $user['total_trackers']; ?>
                                                    </td>
                                                    <td width="20%" align="left" bgcolor="#FFFFFF" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $user['tarif']; ?>
                                                    </td>
                                                    <td width="20%" align="right" bgcolor="#FFFFFF" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: <?php echo $user['sent_sms']? 'red' : 'green' ?>; padding:10px; padding-left:0;">
                                                        <?php
                                                        if($user['white_list']){
                                                            echo 'White list';
                                                        }else{
                                                            echo $user['sent_sms']? 'Trimis' : '-';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            endforeach; ?>
                                            <tr>

                                                <td width="80%" colspan="4" align="right" bgcolor="#FFFFFF" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;">
                                                    <b>Total persoane restante</b>
                                                </td>
                                                <td width="20%" align="right" bgcolor="#FFFFFF" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;">
                                                    <b><?php echo $sent_sms; ?></b>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="middle" style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 14px; color: #353535; padding:3%; padding-top:40px; padding-bottom:40px;">
                                        Raport generat la : <?php echo date('d F Y, H:i:s') ?> <!-- using &nbsp; will prevent orphan words -->
                                    </td>
                                </tr>
                            </table>


                        </td></tr></table>
                <!--[if (gte mso 9)|(IE)]>
                </td></tr></table>
                <![endif]-->
            </td></tr></table></body></html>
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
$mail->Body    = $mail_body;
$mail->AltBody = 'Total SMS trimise catre persoane fizice: ' . $sent_sms;

if(!$mail->send()) {
    echo 'Mail could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
    $daily->errorLog('Mail could not be sent.');
} else {
    echo 'Mail has been sent';
}