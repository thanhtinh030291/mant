<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

if (count($_POST) === 0)
{
    require_once 'overdue_menu.inc.php';
    echo '<br/><h1>Pending Status Report</h1><form method="post">Date: <input type="text" name="date" value="' . date('d/m/Y', strtotime('now')) . '" />&nbsp;<input type="submit" name="search" value="OK" /></form>';
    exit;
}

$date = DateTime::createFromFormat('d/m/Y', $_POST['date'])->format('Y-m-d');

$pendingCounts = getPendingIssueCounts($date);

if (count($pendingCounts) == 0)
{
    die('<h1>Nothing!</h1>');
}

$pendingContent = getPendingIssueCountsContent($pendingCounts);

echo '<!DOCTYPE html><html><body>';
require_once 'overdue_menu.inc.php';
echo '<h1><a href="check_pending_counts_history_xls.php?date=' . $_POST['date'] . '">Download</a></h1>';
displayPendingIssueCounts($pendingContent, '', $_POST['date']);
echo '</body></html>';
