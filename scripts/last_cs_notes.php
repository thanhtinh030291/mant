<?php
require_once '../core.php';
require_once 'cs.inc.php';
require_once 'cs_menu.inc.php';

auth_ensure_user_authenticated();

$LINK = (php_sapi_name() == 'cli' ? "https://pcv-etalk.pacificcross.com.vn/view.php?id=" : "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/last_cs_notes.php','/view.php?id=', $_SERVER['REQUEST_URI']));

if (count($_POST) === 0)
{
    echo '<br/><br/><br/><h1>Last Created Notes</h1><form method="post">From:<input type="text" name="from" value="' . date('d/m/Y', strtotime('-1 days')) . '" />    To:<input type="text" name="to" value="' . date('d/m/Y', strtotime('-1 days')) . '" /><input type="submit" name="search" value="OK" /></form>';
    exit;
}

$from = DateTime::createFromFormat('d/m/Y', $_POST['from'])->format('Y-m-d');
$to = DateTime::createFromFormat('d/m/Y', $_POST['to'])->format('Y-m-d');

$lastNotedIssues = getLastNotedIssues($from, $to);

if (count($lastNotedIssues) == 0)
{
    die('<h1>Nothing!</h1>');
}

echo '<h1>Last Created Notes - <a href="last_cs_notes_xls.php?from='. $from . '&to=' . $to . '">Download</a></h1>';
displayLastNotedIssues($lastNotedIssues);

