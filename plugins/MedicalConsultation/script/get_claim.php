<?php

error_reporting(0);

require_once(dirname(dirname(dirname(__DIR__))) . '/config/config_inc.php');
require_once(dirname(dirname(dirname(__DIR__))) . '/library/taq/pdooci/src/PDO.php');
require_once('constant.inc.php');
require_once('claim_worksheet.inc.php');

try
{
    $hbs = new PDOOCI\PDO(
        "//{$g_hbs_hostname}:{$g_hbs_port}/{$g_hbs_database_name};charset=utf8",
        $g_hbs_username, $g_hbs_password
    );
    $mts = new PDO(
        "mysql:host={$g_hostname};port=3306;dbname={$g_database_name};charset=utf8",
        $g_db_username, $g_db_password
    );
    $api = getApi();

    $issues = getNonConsultedClaimIssues($mts);
    foreach ($issues as $issue)
    {
        $claim = getClaim($hbs, $issue);
        if ($claim != false)
        {
            $id = addMedicalConsultationIssue($api, $issue);
            addMedicalRelationship($mts, $id, $issue['id'], $issue['cl_no'], $issue['common_id']);
        }
    }
}
catch (Exception $e)
{
    echo $e->getMessage();
    exit;
}
