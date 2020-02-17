<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$DAY_MAX = 21;
$LINK = (php_sapi_name() == 'cli' ? "https://pcv-etalk.pacificcross.com.vn/view.php?id=" : "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/check_pendings.php','/view.php?id=', $_SERVER['REQUEST_URI']));

$pendingInfoReceivedIssues = getPendingInfoReceivedIssues();
$pendingIssues = getPendingIssues();

if (count($pendingIssues) == 0 && count($pendingInfoReceivedIssues) == 0)
{
    die('<h1>Nothing!</h1>');
}

if(php_sapi_name() == 'cli')
{
    $mailer = getMailer();
    sendPendingInfoReceivedEmails($mailer, $pendingInfoReceivedIssues);
    sendPendingEmails($mailer, $pendingIssues);
}
else
{
    require_once 'overdue_menu.inc.php';
    echo '<h1><a href="check_pendings_xls.php">Download</a></h1>';
    displayPendingInfoReceivedIssues($pendingInfoReceivedIssues);
    displayPendingIssues($pendingIssues);
}
