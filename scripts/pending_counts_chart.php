<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$pendingCounts = getPendingIssueCounts();
$counts = getIssueCounts();

if (count($counts) == 0 && count($pendingCounts) == 0)
{
    die('<h1>Nothing!</h1>');
}

$pendingChart = getPendingIssueCountsChart($pendingCounts);
$chart = getIssueCountsChart($counts);
if(php_sapi_name() != 'cli')
{
    echo '<!DOCTYPE html><html><body>';

    require_once 'overdue_menu.inc.php';
    displayPendingIssueCountsChart($pendingChart, $chart);

    echo '</body></html>';
}