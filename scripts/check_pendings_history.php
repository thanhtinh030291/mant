<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

if (count($_POST) === 0)
{
    require_once 'overdue_menu.inc.php';
    echo '<br/><h1>Pending Report</h1><form method="post">Date: <input type="text" name="date" value="' . date('d/m/Y', strtotime('now')) . '" />&nbsp;<input type="submit" name="search" value="OK" /></form>';
    exit;
}

$date = DateTime::createFromFormat('d/m/Y', $_POST['date'])->format('Y-m-d');

$DAY_MAX = 21;
$LINK = (php_sapi_name() == 'cli' ? "https://pcv-etalk.pacificcross.com.vn/view.php?id=" : "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/check_pendings.php','/view.php?id=', $_SERVER['REQUEST_URI']));

$pendingInfoReceivedIssues = getPendingInfoReceivedIssues($date);
$pendingIssues = getPendingIssues($date);

if (count($pendingIssues) == 0 && count($pendingInfoReceivedIssues) == 0)
{
    echo '<div><h1>Nothing!</h1></div>';
}

require_once 'overdue_menu.inc.php';
echo '<h1><a href="check_pendings_history_xls.php?date=' . $_POST['date'] . '">Download</a></h1>';
displayPendingInfoReceivedIssues($pendingInfoReceivedIssues, $_POST['date']);
displayPendingIssues($pendingIssues, $_POST['date']);
