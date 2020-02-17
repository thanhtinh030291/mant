<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$DAY_MAX = 0;
$LINK = "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/pending_issues.php','/view.php?id=', $_SERVER['REQUEST_URI']);

$overtimes = getAllPendingIssues();

if (count($overtimes) == 0)
{
    die('<h1>Nothing!</h1>');
}

require_once 'overdue_menu.inc.php';
echo '<h1><a href="pending_issues_xls.php">Download</a></h1>';
displayAllPendingIssues($overtimes);