<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$DAY_MAX = 21;
$LINK = (php_sapi_name() == 'cli' ? "https://pcv-etalk.pacificcross.com.vn/view.php?id=" : "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/check_overdues.php','/view.php?id=', $_SERVER['REQUEST_URI']));

$overdues = getOverdueIssues();
$overtimes = getOvertimeIssues();

if (count($overdues) == 0 && count($overtimes) == 0)
{
    die('<h1>Nothing!</h1>');
}

if(php_sapi_name() == 'cli')
{
    $mailer = getMailer();
    sendOverdueEmails($mailer, $overdues);
    sendOvertimeEmails($mailer, $overtimes);
}
else
{
    require_once 'overdue_menu.inc.php';
    echo '<h1><a href="check_overdues_xls.php">Download</a></h1>';
    displayOverdueIssues($overdues);
    displayOvertimeIssues($overtimes);
}
