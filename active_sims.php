<?php
error_reporting(1);
$start = microtime();
require('ClientChecker.php');
require_once 'PHPMailer-master/PHPMailerAutoload.php';

$daily = new ClientChecker();

$tarrifs = $daily->getTariffList(true);
$tarrifs_gsm = array_filter($tarrifs, function ($tarif) {
    return strpos($tarif->name, 'gsm');
});
echo "Lista de tarife a fost obtinuta \n";

$trackers = $daily->getTrackerList();

echo "Lista de trackere a fost obtinuta \n";
$users = $daily->getUsersList();
echo "Lista de useri a fost obtinuta \n";

$active_trackers_per_tariff = [];

foreach ($trackers as $tracker) {
    if (!$tracker->deleted && !$tracker->clone) {
        $active_trackers_per_tariff[$tracker->source->tariff_id][] = [
            'tracker_id' => $tracker->id,
            'user_id' => $tracker->user_id,
            'sim_number' => $tracker->source->phone];
    }
}


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
                                        <?php echo MAIL_SUBJECT_TRACKERS_PER_TARIF; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <table width="94%" border="0" cellpadding="0" cellspacing="0" style="table-layout: fixed">
                                            <tr>
                                                <td width="10%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Denumire tarif
                                                </td>
                                                <td width="15%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Total trackere
                                                </td>
                                            </tr>
                                            <?php foreach ($tarrifs as $tarif): ?>
                                                <tr>
                                                    <td width="10%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $tarif->name ; ?>
                                                    </td>
                                                    <td width="10%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo count($active_trackers_per_tariff[$tarif->id]); ?>
                                                    </td>

                                                </tr>
                                            <?php endforeach; ?>
                                            <tr>
                                                <td width="20%" align="right" bgcolor="#FFFFFF"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;"></td>
                                                <td width="20%" align="right" bgcolor="#FFFFFF"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-left:0;"></td>
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

$mail->Subject = MAIL_SUBJECT_TRACKERS_PER_TARIF;
$mail->Body = $mail_body;
$mail->AltBody = 'raport trackere per tarif';
$end = microtime();



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
echo 'total seconds' . ($end - $start);
