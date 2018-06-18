<?php
// SMTP SETTINGS
define("SMTP_SERVER",               "smtp.gmail.com");
define("SMTP_USERNAME",             "amoldovanu@best4u.nl");
define("SMTP_PASSWORD",             "PASS C+");
define("SMTP_PORT",                 465);

//MAIL settings
define("MAIL_FROM",                 "amoldovanu@best4u.nl");
define("MAIL_FROM_NAME",            "Tehos rapoarte");
define("MAIL_TO",                   "sandum150@gmail.com");
define("MAIL_REPLY_TO",             "amoldovanu@best4u.nl");
define("MAIL_SUBJECT_PF",           "SMS raport zilnic persoane fizice");
define("MAIL_SUBJECT_PJ_01",        "Raport de suspendare lunar persoane juridice");
define("MAIL_SUBJECT_PJ_1",         "Cont de plata raport lunar persoane juridice");
define("MAIL_SUBJECT_PJ_2",         "Raport lunar persoane juridice restante");

//navixy settings
define("NX_DASHBOARD_ADMIN_URL",    "https://api.navixy.com/v2/panel/");
define("NX_DASHBOARD_USERNAME",     "1111111111");
define("NX_DASHBOARD_PASSWORD",     "1111111111");

//SMS SERVER SETTINGS
define("SMS_SERVER_IP_PORT",        "255.255.255.255:70");
define("SMS_API_USERNAME",          "11111111111");
define("SMS_API_PASSWORD",          "11111111111");
define("SMS_MESSAGE_PF",            "Tehos.md. Aveti mijloace insuficiente");
define("SMS_NEPLATATIT_PF",         "Incepand de astazi, serviciul tehos este sistat pentru neplata");
define("SMS_MESSAGE_PJ",            "Tehos.md. Aveti mijloace insuficiente");
define("SMS_NEPLATA_PJ_DATA_1",     "Incepand de astazi, serviciul tehos este sistat pentru neplata");

// DB settings
define("DB_SERVER_NAME",            "localhost");
define("DB_USER_NAME",              "111111111");
define("DB_DB_NAME",                "111111111");
define("DB_PASSWORD",               "111111111");

// Other
define("SMS_TEST_MODE",             true);
define("MAIL_TEST_MODE",            true);

// JWT
define("JWT_KEY",                   "1111111111111");
define("PASSWORD_KEY",              "1111111111111");