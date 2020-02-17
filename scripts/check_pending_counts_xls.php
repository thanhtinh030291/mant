<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$pendingCounts = getPendingIssueCounts();
$counts = getIssueCounts();

header('Content-type: application/excel');
$filename = 'check_pending_counts.xls';
header('Content-Disposition: attachment; filename='.$filename);

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Sheet 1</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';
if (count($counts) == 0 && count($pendingCounts) == 0)
{
    die('<h1>Nothing!</h1>');
}

$pendingContent = getPendingIssueCountsContent($pendingCounts);
$content = getIssueCountsContent($counts);

displayPendingIssueCounts($pendingContent, $content);

echo '</body></html>';
