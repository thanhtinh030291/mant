<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$LINK = (php_sapi_name() == 'cli' ? "https://pcv-etalk.pacificcross.com.vn/view.php?id=" : "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/check_monitored.php', '/view.php?id=', $_SERVER['REQUEST_URI']));

$monitoredIssues = getMonitoredIssues();

if (count($monitoredIssues) == 0)
{
    die('<h1>Nothing!</h1>');
}

if(php_sapi_name() == 'cli')
{
    $mailer = getMailer();
    sendMonitoredEmails($mailer, $monitoredIssues);
}
else
{
    require_once 'overdue_menu.inc.php';
    echo '<h1><a href="check_monitored_xls.php">Download</a></h1>';
    displayMonitoredIssues($monitoredIssues);
}
