<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$DAY_MAX = 21;
$LINK = "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/check_over_one_week.php','/view.php?id=', $_SERVER['REQUEST_URI']);

$overtimes = getOvertimeIssuesByStatus(14);

if (count($overtimes) == 0)
{
    die('<h1>Nothing!</h1>');
}

require_once 'overdue_menu.inc.php';
echo '<h1><a href="check_over_21_days_pending_xls.php">Download</a></h1>';
displayOvertimeIssues($overtimes);