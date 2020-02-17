<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$date = DateTime::createFromFormat('d/m/Y', $_GET['date'])->format('Y-m-d');

$DAY_MAX = 21;
$LINK = (php_sapi_name() == 'cli' ? "https://pcv-etalk.pacificcross.com.vn/mantis/view.php?id=" : "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/check_pendings.php','/view.php?id=', $_SERVER['REQUEST_URI']));

$pendingInfoReceivedIssues = getPendingInfoReceivedIssues($date);
$pendingIssues = getPendingIssues($date);

header('Content-type: application/excel');
$filename = 'check_pendings_history.xls';
header('Content-Disposition: attachment; filename='.$filename);

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Sheet 1</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';

if (count($pendingIssues) == 0 && count($pendingInfoReceivedIssues) == 0)
{
    echo '<div><h1>Nothing!</h1></div>';
}

displayPendingInfoReceivedIssues($pendingInfoReceivedIssues, $_POST['date']);
displayPendingIssues($pendingIssues, $_POST['date']);

echo '</body></html>';
