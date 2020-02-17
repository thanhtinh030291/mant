<?php
require_once '../core.php';
require_once 'cs.inc.php';

auth_ensure_user_authenticated();

$from = $_GET['from'];
$to = $_GET['to'];

header('Content-type: application/excel');
$filename = 'last_dlvn_cs_notes_' . $from . '_' . $to . '.xls';
header('Content-Disposition: attachment; filename='.$filename);

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Sheet 1</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';

$LINK = (php_sapi_name() == 'cli' ? "https://pcv-etalk.pacificcross.com.vn/view.php?id=" : "https://{$_SERVER['HTTP_HOST']}" . str_replace('/scripts/last_cs_notes_xls.php?from=' . $from . '&to=' . $to,'/view.php?id=', $_SERVER['REQUEST_URI']));

$lastNotedIssues = getLastNotedIssues($from, $to, true);

if (count($lastNotedIssues) == 0)
{
    die('<h1>Nothing!</h1>');
}

displayLastNotedIssues($lastNotedIssues);

echo '</body></html>';
