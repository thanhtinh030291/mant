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

$pendingContent = getPendingIssueCountsContent($pendingCounts);
$content = getIssueCountsContent($counts);
if(php_sapi_name() == 'cli')
{
    $mailer = getMailer();
    sendPendingIssueCountsEmail($mailer, $pendingContent, $content);
}
else
{
    echo '<!DOCTYPE html><html><body>';

    require_once 'overdue_menu.inc.php';
    echo '<h1><a href="check_pending_counts_xls.php">Download</a></h1>';
    displayPendingIssueCounts($pendingContent, $content);

    echo '</body></html>';
}