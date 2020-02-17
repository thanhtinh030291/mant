<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$DAY_MAX = 7;
$LINK = "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/check_over_a_week.php','/view.php?id=', $_SERVER['REQUEST_URI']);

$overtimes = getOvertimeIssues();

if (count($overtimes) == 0)
{
    die('<h1>Nothing!</h1>');
}

require_once 'overdue_menu.inc.php';
echo '<h1><a href="check_over_a_week_xls.php">Download</a></h1>';
displayOvertimeIssues($overtimes);