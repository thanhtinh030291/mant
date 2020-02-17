<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$date = DateTime::createFromFormat('d/m/Y', $_GET['date'])->format('Y-m-d');

$pendingCounts = getPendingIssueCounts($date);

header('Content-type: application/excel');
$filename = 'check_pending_counts_history.xls';
header('Content-Disposition: attachment; filename='.$filename);

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Sheet 1</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';

if (count($pendingCounts) == 0)
{
    die('<h1>Nothing!</h1>');
}

$pendingContent = getPendingIssueCountsContent($pendingCounts);

displayPendingIssueCounts($pendingContent, '', $_POST['date']);
echo '</body></html>';
