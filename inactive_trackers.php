<?php
/** lista cartelelor sim deconectate luna curenta (care au trecut din activ in inactiv).
 *  in tarif trebuie sa fie GSM
 * */
require_once 'ClientChecker.php';
require_once 'PHPMailer-master/PHPMailerAutoload.php';

$time_start = microtime(true);

$navixy = new ClientChecker();

$trackers = $navixy->getTrackerList();
$tarrifs = $navixy->getTariffList(true);

$tarrifs_gsm = array_filter($tarrifs, function ($tarif) {
    return strpos($tarif->name, 'gsm');
});
$gsm_tarifs_ids = [];
foreach ($tarrifs_gsm as $tarif) {
    $gsm_tarifs_ids[] = $tarif->id;
}

$trackers = array_filter($trackers, function ($tracker) use ($gsm_tarifs_ids) {
    return !$tracker->clone && in_array($tracker->source->tariff_id, $gsm_tarifs_ids);
});

$last_month_statuses = $navixy->getAllTrackersStatuses();

$deactivated_trackers = [];
$activated_trackers = [];

foreach ($trackers as $tracker) {
    $found_status_index = array_search($tracker->id, array_column($last_month_statuses, 'tracker_id'));

//    if not found index, this tracker is new. Then we add it
    if ($found_status_index === false && !$tracker->deleted) {
        $activated_trackers[$tracker->id] = [
            'user_id' => $tracker->user_id,
            'tracker_id' => $tracker->id,
            'phone' => $tracker->source->phone,
            'new' => true
        ];
    } else {

        $found_status = $last_month_statuses[$found_status_index];

        if ($tracker->deleted === true && $found_status['status'] === 'active') {
            $deactivated_trackers[$tracker->id] = [
                'user_id' => $tracker->user_id,
                'tracker_id' => $tracker->id,
                'phone' => $tracker->source->phone,
            ];
        } elseif ($tracker->deleted === false && $found_status['status'] === 'inactive') {
            $activated_trackers[$tracker->id] = [
                'user_id' => $tracker->user_id,
                'tracker_id' => $tracker->id,
                'phone' => $tracker->source->phone,
                'new' => false
            ];

        }

    }

    $navixy->setTrackerStatus($tracker->id, $tracker->deleted);

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
                                        <?php echo MAIL_TRACKERS_INACTIVE; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <table width="94%" border="0" cellpadding="0" cellspacing="0" style="table-layout: fixed">
                                            <tr>
                                                <td width="10%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Nr.
                                                </td>
                                                <td width="15%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    User ID
                                                </td>
                                                <td width="25%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Tracker ID
                                                </td>
                                                <td width="18%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    GSM
                                                </td>

                                            </tr>
                                            <?php
                                            $nr = 1;
                                            foreach ($deactivated_trackers as $tracker_id => $tracker):
                                                    ?>
                                                    <tr>
                                                        <td width="10%" align="left" bgcolor="#FFFFFF"
                                                            style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                            <?php echo $nr; ?>
                                                        </td>
                                                        <td width="10%" align="left" bgcolor="#FFFFFF"
                                                            style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                            <?php echo $tracker['user_id']; ?>
                                                        </td>
                                                        <td width="30%" align="left" bgcolor="#FFFFFF"
                                                            style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                            <?php echo $tracker['tracker_id']; ?>
                                                        </td>
                                                        <td width="20%" align="left" bgcolor="#FFFFFF"
                                                            style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px;">
                                                            <?php echo $tracker['phone']; ?>
                                                        </td>
                                                    </tr>
                                            <?php
                                            $nr++;
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
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>


                        </td>
                    </tr>
                </table>


                <table width="100%" class="maxW" style="max-width: 700px; margin: auto;" border="0" align="center"
                       cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" align="center">


                            <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF">
                                <tr>
                                    <td align="left" valign="middle"
                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 24px; color: #353535; padding:3%; padding-top:40px; padding-bottom:40px;">
                                        <?php echo MAIL_TRACKERS_ACTIVE; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <table width="94%" border="0" cellpadding="0" cellspacing="0" style="table-layout: fixed">
                                            <tr>
                                                <td width="10%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Nr.
                                                </td>
                                                <td width="15%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    User ID
                                                </td>
                                                <td width="25%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    Tracker ID
                                                </td>
                                                <td width="18%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    GSM
                                                </td>
                                                <td width="18%" align="left" bgcolor="#252525"
                                                    style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #EEEEEE; padding:10px; padding-right:0;">
                                                    NOU
                                                </td>

                                            </tr>
                                            <?php
                                            $nr = 1;
                                            foreach ($activated_trackers as $tracker_id => $tracker):
                                                ?>
                                                <tr>
                                                    <td width="10%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $nr; ?>
                                                    </td>
                                                    <td width="10%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $tracker['user_id']; ?>
                                                    </td>
                                                    <td width="30%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px; padding-right:0;">
                                                        <?php echo $tracker['tracker_id']; ?>
                                                    </td>
                                                    <td width="20%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px;">
                                                        <?php echo $tracker['phone']; ?>
                                                    </td>
                                                    <td width="20%" align="left" bgcolor="#FFFFFF"
                                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 12px; color: #252525; padding:10px;">
                                                        <?php echo $tracker['new'] ? 'DA' : 'NU'; ?>
                                                    </td>
                                                </tr>
                                                <?php
                                                $nr++;
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
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="middle"
                                        style="font-family: Verdana, Geneva, Helvetica, Arial, sans-serif; font-size: 14px; color: #353535; padding:3%; padding-top:40px; padding-bottom:40px;">
                                        <?php
                                        $time_end = microtime(true);
                                        $time = $time_end - $time_start; ?>
                                        Raport generat la : <?php echo date('d F Y, H:i:s') ?> <br>
                                        Durata de executie: <?php echo number_format($time, 2); ?> secunde.
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

$mail->Subject = MAIL_SUBJECT_TRACKERS_INACTIVE;
$mail->Body = $mail_body;
$mail->AltBody = 'Total SMS trimise catre persoane fizice: ';

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
