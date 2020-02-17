<?php
require_once '../core.php';
require_once 'overdue.inc.php';

auth_ensure_user_authenticated();

$DAY_MAX = 7;
$LINK = "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/check_over_one_week.php','/view.php?id=', $_SERVER['REQUEST_URI']);

$overtimes = getOvertimeIssuesByStatus(14);

header('Content-type: application/excel');
$filename = 'check_over_7_days_pending.xls';
header('Content-Disposition: attachment; filename='.$filename);

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Sheet 1</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';
if (count($overtimes) == 0)
{
    die('<h1>Nothing!</h1>');
}

displayOvertimeIssues($overtimes);

echo '</body></html>';
