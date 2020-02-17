<?php
require_once __DIR__ . '/phpmailer/phpmailer/PHPMailerAutoload.php';

$FALLBACK_EMAIL = array
(
    'name' => 'Tống Thị Thắm',
    'email' => 'thamtong@pacificcross.com.vn'
);

$STATUS_LIST = array
(
    10 => 'new',
    11 => 'accepted',
    12 => 'partiallyaccepted',
    13 => 'declined',
    14 => 'pending',
    15 => 'reopen',
    16 => 'inforequest',
    17 => 'inforeceived',
    18 => 'readyforprocess',
    20 => 'feedback',
    30 => 'acknowledged',
    40 => 'confirmed',
    50 => 'assigned',
    60 => 'open',
    80 => 'resolved',
    90 => 'closed'
);

$STATUS_COLOR_LIST = array
(
    10 => 'fcbdbd',
    11 => '00ff00',
    12 => '00ffff',
    13 => 'ff0000',
    14 => 'ffff00',
    15 => 'c0c0c0',
    16 => 'f0af00',
    17 => 'a05670',
    18 => '5ac074',
    20 => 'e3b7eb',
    30 => 'ffcd85',
    40 => 'fff494',
    50 => 'c2dfff',
    60 => 'ffffff',
    80 => 'd2f5b0',
    90 => 'c9ccc4',
);

$HAVE_OVERDUE_STATUS_LIST = array
(
    10 => 1,
    14 => 2,
    50 => 2,
    16 => 7,
    17 => 1,
    18 => 2
);

$NEVER_OVERTIME_STATUS_LIST = array(11, 12, 13, 80, 90);

$INFO_RECEIVED_LIMIT = 5;

$PENDING_LIST = array(10, 14, 15, 16, 17, 18, 20, 30, 40, 50, 60);
$PENDING_LIMIT = 3;

function getMailer()
{
    $mailer = new PHPMailer(true);

    $mailer->IsSMTP(true);
    $mailer->SMTPDebug = false;
    $mailer->SMTPAuth = true;
    $mailer->CharSet = "utf-8";

    $mailer->Host = 'localhost';
    $mailer->Port = 25;
    $mailer->Username = 'admin@pacificcross.vn';
    $mailer->Password = '@dmin##vdp';
    $mailer->SetFrom('admin@pacificcross.vn', 'PCV TAT Checker');
    $mailer->AddReplyTo('admin@pacificcross.vn', 'PCV TAT Checker');

    return $mailer;
}

function sendMail($mailer, $receivers, $subject, $message)
{
    global $FALLBACK_EMAIL;
    //echo "Sending Email to: " . json_encode($receivers);
    try
    {
        foreach ($receivers as $name => $email)
        {
            if (!isset($email) || strlen($email) == 0)
            {
                $mailer->addAddress($FALLBACK_EMAIL['email'], $FALLBACK_EMAIL['name']);
            }
            else
            {
                $mailer->addAddress($email, $name);
            }
        }

        $mailer->Subject = $subject;
        $mailer->Body = $message;
        $mailer->IsHTML(true);

        $result = $mailer->send();
        //echo $result == true ? " OK\n" : " Failed\n";
        return $result;
    }
    catch (Exception $e)
    {
        //echo " Error " . json_encode($e) . "\n";
        return false;
    }
}

function getOverdueIssues()
{
    global $HAVE_OVERDUE_STATUS_LIST, $DAY_MAX;
    $results = array();
    foreach ($HAVE_OVERDUE_STATUS_LIST as $code => $value)
    {
        $statusSql = "SELECT DISTINCT
                         BUG.`id`,
                         BUG.`summary`,
                         BUG.`status`,
                         HANDLER.`email` AS 'handler_email',
                         HANDLER.`realname` AS 'handler_realname',
                         DATE_FORMAT(FROM_UNIXTIME(BUG.`last_updated`), '%m/%d/%Y %H:%i:%s') as 'last_updated',
                         DATE_FORMAT(FROM_UNIXTIME(HISTORY.`date_modified`), '%m/%d/%Y %H:%i:%s') as 'last_status_changed',
                         COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) as 'working_days'
                      FROM `mantis_bug_table` BUG
                        INNER JOIN `mantis_project_table` PROJECT
                                ON BUG.`project_id` = PROJECT.`id`
                               AND PROJECT.`name` LIKE 'CLM - %'
                        INNER JOIN `mantis_bug_history_table` HISTORY
                                ON BUG.`id` = HISTORY.`bug_id`
                               AND BUG.`status` = HISTORY.`new_value`
                               AND HISTORY.`field_name` = 'status'
                         LEFT JOIN `mantis_user_table` HANDLER
                                ON BUG.`handler_id` = HANDLER.`id`
                      WHERE BUG.`status` = {$code}
                        AND BUG.`summary` NOT LIKE '%GOP%'
                        AND COUNT_WORKING_DAYS(FROM_UNIXTIME(HISTORY.`date_modified`), NOW()) > {$value}
                        AND COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) <= {$DAY_MAX}
                      GROUP BY BUG.`id`
                      ORDER BY `handler_email` ASC, HISTORY.`date_modified` ASC";
        $statusQuery = db_query($statusSql);
        while ($queryResult = db_fetch_array($statusQuery))
        {
            if (!isset($results[$queryResult['handler_realname']]))
            {
                $results[$queryResult['handler_realname']] = array($queryResult['handler_email'] => array());
            }
            if (!isset($results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']]))
            {
                $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']] = array();
            }
            $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']][] = array
            (
                'id' => $queryResult['id'],
                'summary' => $queryResult['summary'],
                'status' => $queryResult['status'],
                'last_updated' => $queryResult['last_updated'],
                'last_status_changed' => $queryResult['last_status_changed'],
                'working_days' => $queryResult['working_days'],
                'handler_realname' => $queryResult['handler_realname'],
                'handler_email' => $queryResult['handler_email']
            );
        }
    }
    return $results;
}

function getOvertimeIssues()
{
    global $NEVER_OVERTIME_STATUS_LIST, $DAY_MAX;
    $results = array();
    $neverOvertimeStatuses = implode(',', $NEVER_OVERTIME_STATUS_LIST);
    $statusSql = "SELECT DISTINCT
                     BUG.`id`,
                     BUG.`summary`, BUG.`status`,
                     HANDLER.`email` AS 'handler_email',
                     HANDLER.`realname` AS 'handler_realname',
                     DATE_FORMAT(FROM_UNIXTIME(BUG.`last_updated`), '%m/%d/%Y %H:%i:%s') as 'last_updated',
                     DATE_FORMAT(FROM_UNIXTIME(BUG.`date_submitted`), '%m/%d/%Y %H:%i:%s') as 'date_submitted',
                     COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) as 'working_days'
                  FROM `mantis_bug_table` BUG
                    INNER JOIN `mantis_project_table` PROJECT
                            ON BUG.`project_id` = PROJECT.`id`
                           AND PROJECT.`name` LIKE 'CLM - %'
                    INNER JOIN `mantis_bug_history_table` HISTORY
                            ON BUG.`id` = HISTORY.`bug_id`
                           AND BUG.`status` = HISTORY.`new_value`
                           AND HISTORY.`field_name` = 'status'
                     LEFT JOIN `mantis_user_table` HANDLER
                            ON BUG.`handler_id` = HANDLER.`id`
                  WHERE BUG.`status` NOT IN ({$neverOvertimeStatuses})
                    AND BUG.`summary` NOT LIKE '%GOP%'
                    AND COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) > {$DAY_MAX}
                  ORDER BY `handler_email` ASC, HISTORY.`date_modified` ASC";
    $statusQuery = db_query($statusSql);
    while ($queryResult = db_fetch_array($statusQuery))
    {
        if (!isset($results[$queryResult['handler_realname']]))
        {
            $results[$queryResult['handler_realname']] = array($queryResult['handler_email'] => array());
        }
        if (!isset($results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']]))
        {
            $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']] = array();
        }
        $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']][] = array
        (
            'id' => $queryResult['id'],
            'summary' => $queryResult['summary'],
            'status' => $queryResult['status'],
            'last_updated' => $queryResult['last_updated'],
            'date_submitted' => $queryResult['date_submitted'],
            'working_days' => $queryResult['working_days'],
            'handler_realname' => $queryResult['handler_realname'],
            'handler_email' => $queryResult['handler_email']
        );
    }
    return $results;
}

function getOvertimeIssuesByStatus($status)
{
    global $NEVER_OVERTIME_STATUS_LIST, $DAY_MAX;
    $results = array();
    $statusSql = "SELECT DISTINCT
                     BUG.`id`,
                     BUG.`summary`,
                     BUG.`status`,
                     HANDLER.`email` AS 'handler_email',
                     HANDLER.`realname` AS 'handler_realname',
                     DATE_FORMAT(FROM_UNIXTIME(BUG.`last_updated`), '%m/%d/%Y %H:%i:%s') as 'last_updated',
                     DATE_FORMAT(FROM_UNIXTIME(BUG.`date_submitted`), '%m/%d/%Y %H:%i:%s') as 'date_submitted',
                     COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) as 'working_days'
                  FROM `mantis_bug_table` BUG
                    INNER JOIN `mantis_project_table` PROJECT
                            ON BUG.`project_id` = PROJECT.`id`
                           AND PROJECT.`name` LIKE 'CLM - %'
                    INNER JOIN `mantis_bug_history_table` HISTORY
                            ON BUG.`id` = HISTORY.`bug_id`
                           AND BUG.`status` = HISTORY.`new_value`
                           AND HISTORY.`field_name` = 'status'
                     LEFT JOIN `mantis_user_table` HANDLER
                            ON BUG.`handler_id` = HANDLER.`id`
                  WHERE BUG.`status` = {$status}
                    AND BUG.`summary` NOT LIKE '%GOP%'
                    AND COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) > {$DAY_MAX}
                  ORDER BY `handler_email` ASC, HISTORY.`date_modified` ASC";
    $statusQuery = db_query($statusSql);
    while ($queryResult = db_fetch_array($statusQuery))
    {
        if (!isset($results[$queryResult['handler_realname']]))
        {
            $results[$queryResult['handler_realname']] = array($queryResult['handler_email'] => array());
        }
        if (!isset($results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']]))
        {
            $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']] = array();
        }
        $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']][] = array
        (
            'id' => $queryResult['id'],
            'summary' => $queryResult['summary'],
            'status' => $queryResult['status'],
            'last_updated' => $queryResult['last_updated'],
            'date_submitted' => $queryResult['date_submitted'],
            'working_days' => $queryResult['working_days'],
            'handler_realname' => $queryResult['handler_realname'],
            'handler_email' => $queryResult['handler_email']
        );
    }
    return $results;
}

function getAllPendingIssues()
{
    global $HAVE_OVERDUE_STATUS_LIST;
    $results = array();
    foreach ($HAVE_OVERDUE_STATUS_LIST as $code => $value)
    {
        $statusSql = "SELECT DISTINCT
                         BUG.`id`,
                         BUG.`summary`,
                         BUG.`status`,
                         DATE_FORMAT(FROM_UNIXTIME(BUG.`date_submitted`), '%m/%d/%Y %H:%i:%s') `date_submitted`,
                         HANDLER.`email` AS 'handler_email',
                         HANDLER.`realname` AS 'handler_realname',
                         DATE_FORMAT(FROM_UNIXTIME(BUG.`last_updated`), '%m/%d/%Y %H:%i:%s') as 'last_updated',
                         COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) as 'working_days',
                         COALESCE(REASON.`value`, '') AS 'reason',
                         COALESCE(CLAIM.`value`, '') AS 'claim_no'
                      FROM `mantis_bug_table` BUG
                        INNER JOIN `mantis_project_table` PROJECT
                                ON BUG.`project_id` = PROJECT.`id`
                               AND PROJECT.`name` LIKE 'CLM - %'
                         LEFT JOIN `mantis_custom_field_string_table` REASON
                                ON BUG.`id` = REASON.`bug_id`
                               AND REASON.`field_id` IN (SELECT `id` FROM `mantis_custom_field_table`
                                                         WHERE `name` = 'Reason for Pending')
                         LEFT JOIN `mantis_custom_field_string_table` CLAIM
                                ON BUG.`id` = CLAIM.`bug_id`
                               AND CLAIM.`field_id` IN (SELECT `id` FROM `mantis_custom_field_table`
                                                        WHERE `name` = 'Claim No')
                         LEFT JOIN `mantis_user_table` HANDLER
                                ON BUG.`handler_id` = HANDLER.`id`
                      WHERE BUG.`status` = {$code}
                        AND BUG.`summary` NOT LIKE '%GOP%'
                      GROUP BY BUG.`id`
                      ORDER BY `handler_email` ASC";
        $statusQuery = db_query($statusSql);
        while ($queryResult = db_fetch_array($statusQuery))
        {
            if (!isset($results[$queryResult['handler_realname']]))
            {
                $results[$queryResult['handler_realname']] = array($queryResult['handler_email'] => array());
            }
            if (!isset($results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']]))
            {
                $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']] = array();
            }
            $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']][] = array
            (
                'id' => $queryResult['id'],
                'summary' => $queryResult['summary'],
                'status' => $queryResult['status'],
                'date_submitted' => $queryResult['date_submitted'],
                'last_updated' => $queryResult['last_updated'],
                'last_status_changed' => $queryResult['last_status_changed'],
                'working_days' => $queryResult['working_days'],
                'handler_realname' => $queryResult['handler_realname'],
                'handler_email' => $queryResult['handler_email'],
                'reason' => $queryResult['reason'],
                'claim_no' => $queryResult['claim_no']
            );
        }
    }
    return $results;
}

function getPendingIssues($date = null)
{
    global $HAVE_OVERDUE_STATUS_LIST, $PENDING_LIMIT;
    $results = array();
    foreach ($HAVE_OVERDUE_STATUS_LIST as $code => $value)
    {
        if (is_null($date))
        {
            $statusSql = "SELECT DISTINCT
                             BUG.`id`,
                             BUG.`summary`,
                             BUG.`status`,
                             HANDLER.`email` AS 'handler_email',
                             HANDLER.`realname` AS 'handler_realname',
                             DATE_FORMAT(FROM_UNIXTIME(BUG.`last_updated`), '%m/%d/%Y %H:%i:%s') as 'last_updated',
                             DATE_FORMAT(FROM_UNIXTIME(HISTORY.`date_modified`), '%m/%d/%Y %H:%i:%s') as 'last_status_changed',
                             COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) as 'working_days'
                          FROM `mantis_bug_table` BUG
                            INNER JOIN `mantis_project_table` PROJECT
                                    ON BUG.`project_id` = PROJECT.`id`
                                   AND PROJECT.`name` LIKE 'CLM - %'
                            INNER JOIN (SELECT `bug_id`, `new_value`, `date_modified`
                                        FROM `mantis_bug_history_table`
                                        WHERE `field_name` = 'status'
                                        GROUP BY `bug_id`, `new_value`, `date_modified`
                                        HAVING `date_modified` = MAX(`date_modified`)) HISTORY
                                    ON BUG.`id` = HISTORY.`bug_id`
                                   AND BUG.`status` = HISTORY.`new_value`
                             LEFT JOIN `mantis_user_table` HANDLER
                                    ON BUG.`handler_id` = HANDLER.`id`
                          WHERE BUG.`status` = {$code}
                            AND BUG.`summary` NOT LIKE '%GOP%'
                            AND COUNT_WORKING_DAYS(FROM_UNIXTIME(HISTORY.`date_modified`), NOW()) > {$PENDING_LIMIT}
                          GROUP BY BUG.`id`
                          ORDER BY `handler_email` ASC, HISTORY.`date_modified` ASC";
        }
        else
        {
            $statusSql = "SELECT DISTINCT
                             BUG.`id`,
                             BUG.`summary`,
                             BUG.`status`,
                             HANDLER.`email` AS 'handler_email',
                             HANDLER.`realname` AS 'handler_realname',
                             DATE_FORMAT(FROM_UNIXTIME(HISTORY2.`date_modified`), '%m/%d/%Y %H:%i:%s') as 'last_updated',
                             DATE_FORMAT(FROM_UNIXTIME(HISTORY.`date_modified`), '%m/%d/%Y %H:%i:%s') as 'last_status_changed',
                             COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) as 'working_days'
                          FROM (SELECT
                                    `bug`.`id`,
                                    `bug`.`date_submitted`,
                                    `bug`.`last_updated`,
                                    CASE WHEN `bug_summary`.`new_value` IS NULL THEN `bug`.`summary` ELSE `bug_summary`.`new_value` END `summary`,
                                    CASE WHEN `bug_status`.`new_value` IS NULL THEN `bug`.`status` ELSE `bug_status`.`new_value` END `status`,
                                    CASE WHEN `bug_project`.`new_value` IS NULL THEN `bug`.`project_id` ELSE `bug_project`.`new_value` END `project_id`,
                                    CASE WHEN `bug_handler`.`new_value` IS NULL THEN `bug`.`handler_id` ELSE `bug_handler`.`new_value` END `handler_id`
                                FROM `mantis_bug_table` `bug`
                                    LEFT JOIN `mantis_bug_history_table` `bug_summary`
                                           ON `bug_summary`.`id` = (SELECT `bug_summary_internal`.`id`
                                                                    FROM `mantis_bug_history_table` `bug_summary_internal`
                                                                    WHERE `bug`.`id` = `bug_summary_internal`.`bug_id`
                                                                      AND `bug_summary_internal`.`field_name` = 'summary'
                                                                      AND `bug_summary_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                    ORDER BY `bug_summary_internal`.`date_modified` DESC
                                                                    LIMIT 1)
                                    LEFT JOIN `mantis_bug_history_table` `bug_status`
                                           ON `bug_status`.`id` = (SELECT `bug_status_internal`.`id`
                                                                    FROM `mantis_bug_history_table` `bug_status_internal`
                                                                    WHERE `bug`.`id` = `bug_status_internal`.`bug_id`
                                                                      AND `bug_status_internal`.`field_name` = 'status'
                                                                      AND `bug_status_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                    ORDER BY `bug_status_internal`.`date_modified` DESC
                                                                    LIMIT 1)
                                    LEFT JOIN `mantis_bug_history_table` `bug_project`
                                           ON `bug_project`.`id` = (SELECT `bug_project_internal`.`id`
                                                                    FROM `mantis_bug_history_table` `bug_project_internal`
                                                                    WHERE `bug`.`id` = `bug_project_internal`.`bug_id`
                                                                      AND `bug_project_internal`.`field_name` = 'project_id'
                                                                      AND `bug_project_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                    ORDER BY `bug_project_internal`.`date_modified` DESC
                                                                    LIMIT 1)
                                    LEFT JOIN `mantis_bug_history_table` `bug_handler`
                                           ON `bug_handler`.`id` = (SELECT `bug_handler_internal`.`id`
                                                                    FROM `mantis_bug_history_table` `bug_handler_internal`
                                                                    WHERE `bug`.`id` = `bug_handler_internal`.`bug_id`
                                                                      AND `bug_handler_internal`.`field_name` = 'handler_id'
                                                                      AND `bug_handler_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                    ORDER BY `bug_handler_internal`.`date_modified` DESC
                                                                    LIMIT 1)) BUG
                            INNER JOIN `mantis_project_table` PROJECT
                                    ON BUG.`project_id` = PROJECT.`id`
                                   AND PROJECT.`name` LIKE 'CLM - %'
                            INNER JOIN (SELECT `bug_id`, `new_value`, `date_modified`
                                        FROM `mantis_bug_history_table`
                                        WHERE `field_name` = 'status'
                                          AND `date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                        GROUP BY `bug_id`, `new_value`, `date_modified`
                                        HAVING `date_modified` = MAX(`date_modified`)) HISTORY
                                    ON BUG.`id` = HISTORY.`bug_id`
                                   AND BUG.`status` = HISTORY.`new_value`
                            INNER JOIN (SELECT `bug_id`, `date_modified`
                                        FROM `mantis_bug_history_table`
                                        WHERE `date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                        GROUP BY `bug_id`, `date_modified`
                                        HAVING `date_modified` = MAX(`date_modified`)) HISTORY2
                                    ON BUG.`id` = HISTORY2.`bug_id`
                             LEFT JOIN `mantis_user_table` HANDLER
                                    ON BUG.`handler_id` = HANDLER.`id`
                          WHERE BUG.`status` = {$code}
                            AND BUG.`summary` NOT LIKE '%GOP%'
                            AND COUNT_WORKING_DAYS(FROM_UNIXTIME(HISTORY.`date_modified`), NOW()) > {$PENDING_LIMIT}
                          GROUP BY BUG.`id`
                          ORDER BY `handler_email` ASC, HISTORY.`date_modified` ASC";
        }
        $statusQuery = db_query($statusSql);
        while ($queryResult = db_fetch_array($statusQuery))
        {
            if (!isset($results[$queryResult['handler_realname']]))
            {
                $results[$queryResult['handler_realname']] = array($queryResult['handler_email'] => array());
            }
            if (!isset($results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']]))
            {
                $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']] = array();
            }
            $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']][] = array
            (
                'id' => $queryResult['id'],
                'summary' => $queryResult['summary'],
                'status' => $queryResult['status'],
                'last_updated' => $queryResult['last_updated'],
                'last_status_changed' => $queryResult['last_status_changed'],
                'working_days' => $queryResult['working_days'],
                'handler_realname' => $queryResult['handler_realname'],
                'handler_email' => $queryResult['handler_email']
            );
        }
    }
    return $results;
}

function getPendingInfoReceivedIssues($date = null)
{
    global $INFO_RECEIVED_LIMIT;
    $results = array();
    if (is_null($date))
    {
        $statusSql = "SELECT DISTINCT
                         BUG.`id`,
                         BUG.`summary`,
                         BUG.`status`,
                         HANDLER.`email` AS 'handler_email',
                         HANDLER.`realname` AS 'handler_realname',
                         DATE_FORMAT(FROM_UNIXTIME(BUG.`last_updated`), '%m/%d/%Y %H:%i:%s') as 'last_updated',
                         DATE_FORMAT(FROM_UNIXTIME(HISTORY.`date_modified`), '%m/%d/%Y %H:%i:%s') as 'last_status_changed',
                         COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) as 'working_days'
                      FROM `mantis_bug_table` BUG
                        INNER JOIN `mantis_project_table` PROJECT
                                ON BUG.`project_id` = PROJECT.`id`
                               AND PROJECT.`name` LIKE 'CLM - %'
                        INNER JOIN (SELECT `bug_id`, `new_value`, `date_modified`
                                    FROM `mantis_bug_history_table`
                                    WHERE `field_name` = 'status'
                                    GROUP BY `bug_id`, `new_value`, `date_modified`
                                    HAVING `date_modified` = MAX(`date_modified`)) HISTORY
                                ON BUG.`id` = HISTORY.`bug_id`
                               AND BUG.`status` = HISTORY.`new_value`
                         LEFT JOIN `mantis_user_table` HANDLER
                                ON BUG.`handler_id` = HANDLER.`id`
                      WHERE BUG.`status` = 105
                        AND BUG.`summary` NOT LIKE '%GOP%'
                        AND COUNT_WORKING_DAYS(FROM_UNIXTIME(HISTORY.`date_modified`), NOW()) > {$INFO_RECEIVED_LIMIT}
                      GROUP BY BUG.`id`
                      ORDER BY `handler_email` ASC, HISTORY.`date_modified` ASC";
    }
    else
    {
        $statusSql = "SELECT DISTINCT
                         BUG.`id`,
                         BUG.`summary`,
                         BUG.`status`,
                         HANDLER.`email` AS 'handler_email',
                         HANDLER.`realname` AS 'handler_realname',
                         DATE_FORMAT(FROM_UNIXTIME(HISTORY2.`date_modified`), '%m/%d/%Y %H:%i:%s') as 'last_updated',
                         DATE_FORMAT(FROM_UNIXTIME(HISTORY.`date_modified`), '%m/%d/%Y %H:%i:%s') as 'last_status_changed',
                         COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) as 'working_days'
                      FROM (SELECT
                                `bug`.`id`,
                                `bug`.`date_submitted`,
                                `bug`.`last_updated`,
                                CASE WHEN `bug_summary`.`new_value` IS NULL THEN `bug`.`summary` ELSE `bug_summary`.`new_value` END `summary`,
                                CASE WHEN `bug_status`.`new_value` IS NULL THEN `bug`.`status` ELSE `bug_status`.`new_value` END `status`,
                                CASE WHEN `bug_project`.`new_value` IS NULL THEN `bug`.`project_id` ELSE `bug_project`.`new_value` END `project_id`,
                                CASE WHEN `bug_handler`.`new_value` IS NULL THEN `bug`.`handler_id` ELSE `bug_handler`.`new_value` END `handler_id`
                            FROM `mantis_bug_table` `bug`
                                LEFT JOIN `mantis_bug_history_table` `bug_summary`
                                       ON `bug_summary`.`id` = (SELECT `bug_summary_internal`.`id`
                                                                FROM `mantis_bug_history_table` `bug_summary_internal`
                                                                WHERE `bug`.`id` = `bug_summary_internal`.`bug_id`
                                                                  AND `bug_summary_internal`.`field_name` = 'summary'
                                                                  AND `bug_summary_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                ORDER BY `bug_summary_internal`.`date_modified` DESC
                                                                LIMIT 1)
                                LEFT JOIN `mantis_bug_history_table` `bug_status`
                                       ON `bug_status`.`id` = (SELECT `bug_status_internal`.`id`
                                                                FROM `mantis_bug_history_table` `bug_status_internal`
                                                                WHERE `bug`.`id` = `bug_status_internal`.`bug_id`
                                                                  AND `bug_status_internal`.`field_name` = 'status'
                                                                  AND `bug_status_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                ORDER BY `bug_status_internal`.`date_modified` DESC
                                                                LIMIT 1)
                                LEFT JOIN `mantis_bug_history_table` `bug_project`
                                       ON `bug_project`.`id` = (SELECT `bug_project_internal`.`id`
                                                                FROM `mantis_bug_history_table` `bug_project_internal`
                                                                WHERE `bug`.`id` = `bug_project_internal`.`bug_id`
                                                                  AND `bug_project_internal`.`field_name` = 'project_id'
                                                                  AND `bug_project_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                ORDER BY `bug_project_internal`.`date_modified` DESC
                                                                LIMIT 1)
                                LEFT JOIN `mantis_bug_history_table` `bug_handler`
                                       ON `bug_handler`.`id` = (SELECT `bug_handler_internal`.`id`
                                                                FROM `mantis_bug_history_table` `bug_handler_internal`
                                                                WHERE `bug`.`id` = `bug_handler_internal`.`bug_id`
                                                                  AND `bug_handler_internal`.`field_name` = 'handler_id'
                                                                  AND `bug_handler_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                ORDER BY `bug_handler_internal`.`date_modified` DESC
                                                                LIMIT 1)) BUG
                        INNER JOIN `mantis_project_table` PROJECT
                                ON BUG.`project_id` = PROJECT.`id`
                               AND PROJECT.`name` LIKE 'CLM - %'
                        INNER JOIN (SELECT `bug_id`, `new_value`, `date_modified`
                                    FROM `mantis_bug_history_table`
                                    WHERE `field_name` = 'status'
                                      AND `date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                    GROUP BY `bug_id`, `new_value`, `date_modified`
                                    HAVING `date_modified` = MAX(`date_modified`)) HISTORY
                                ON BUG.`id` = HISTORY.`bug_id`
                               AND BUG.`status` = HISTORY.`new_value`
                        INNER JOIN (SELECT `bug_id`, `date_modified`
                                    FROM `mantis_bug_history_table`
                                    WHERE `date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                    GROUP BY `bug_id`, `date_modified`
                                    HAVING `date_modified` = MAX(`date_modified`)) HISTORY2
                                ON BUG.`id` = HISTORY2.`bug_id`
                         LEFT JOIN `mantis_user_table` HANDLER
                                ON BUG.`handler_id` = HANDLER.`id`
                      WHERE BUG.`status` = 105
                        AND BUG.`summary` NOT LIKE '%GOP%'
                        AND COUNT_WORKING_DAYS(FROM_UNIXTIME(HISTORY.`date_modified`), NOW()) > {$INFO_RECEIVED_LIMIT}
                      GROUP BY BUG.`id`
                      ORDER BY `handler_email` ASC, HISTORY.`date_modified` ASC";
    }
    $statusQuery = db_query($statusSql);
    while ($queryResult = db_fetch_array($statusQuery))
    {
        if (!isset($results[$queryResult['handler_realname']]))
        {
            $results[$queryResult['handler_realname']] = array($queryResult['handler_email'] => array());
        }
        if (!isset($results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']]))
        {
            $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']] = array();
        }
        $results[$queryResult['handler_realname']][$queryResult['handler_email']][$queryResult['status']][] = array
        (
            'id' => $queryResult['id'],
            'summary' => $queryResult['summary'],
            'status' => $queryResult['status'],
            'last_updated' => $queryResult['last_updated'],
            'last_status_changed' => $queryResult['last_status_changed'],
            'working_days' => $queryResult['working_days'],
            'handler_realname' => $queryResult['handler_realname'],
            'handler_email' => $queryResult['handler_email']
        );
    }
    return $results;
}

function getPendingIssueCounts($date = null)
{
    global $STATUS_LIST, $PENDING_LIST;
    $pendingStatuses = implode(',', $PENDING_LIST);
    if (is_null($date))
    {
        $statusSql = "(SELECT
                        CASE
                            WHEN (HANDLER.`realname` IS NULL) THEN 'Un-Assigned'
                            ELSE HANDLER.`realname`
                        END AS 'handler_realname',
                        BUG.`status`,
                        COUNT(BUG.`status`) AS 'count',
                        CASE WHEN BUG.`summary` NOT LIKE '%GOP%' THEN 'non-gop' ELSE 'gop' END AS 'gop'
                       FROM `mantis_bug_table` BUG
                        INNER JOIN `mantis_project_table` PROJECT
                                ON BUG.`project_id` = PROJECT.`id`
                               AND PROJECT.`name` LIKE 'CLM - %'
                         LEFT JOIN `mantis_user_table` HANDLER
                                ON BUG.`handler_id` = HANDLER.`id`
                       WHERE BUG.`status` IN ({$pendingStatuses})
                       GROUP BY HANDLER.`email`, BUG.`status`
                       ORDER BY `handler_realname`)";
    }
    else
    {
        $statusSql = "(SELECT
                        CASE
                            WHEN (`handler`.`realname` IS NULL) THEN 'Un-Assigned'
                            ELSE `handler`.`realname`
                        END AS 'handler_realname',
                        `bug`.`gop`,
                        `bug`.`status`,
                        COUNT(`bug`.`status`) AS `count`
                       FROM (SELECT
                                `bug`.`id`,
                                CASE WHEN (CASE WHEN `bug_summary`.`new_value` IS NULL THEN `bug`.`summary` ELSE `bug_summary`.`new_value` END) LIKE '%GOP%' THEN 'gop' ELSE 'non-gop' END `gop`,
                                CASE WHEN `bug_status`.`new_value` IS NULL THEN `bug`.`status` ELSE `bug_status`.`new_value` END `status`
                                CASE WHEN `bug_project`.`new_value` IS NULL THEN `bug`.`project_id` ELSE `bug_project`.`new_value` END `project_id`,
                                CASE WHEN `bug_handler`.`new_value` IS NULL THEN `bug`.`handler_id` ELSE `bug_handler`.`new_value` END `handler_id`,
                             FROM `mantis_bug_table` `bug`
                                LEFT JOIN `mantis_bug_history_table` `bug_summary`
                                       ON `bug_summary`.`id` = (SELECT `bug_summary_internal`.`id`
                                                                FROM `mantis_bug_history_table` `bug_summary_internal`
                                                                WHERE `bug`.`id` = `bug_summary_internal`.`bug_id`
                                                                  AND `bug_summary_internal`.`field_name` = 'summary'
                                                                  AND `bug_summary_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                ORDER BY `bug_summary_internal`.`date_modified` DESC
                                                                LIMIT 1)
                                LEFT JOIN `mantis_bug_history_table` `bug_status`
                                       ON `bug_status`.`id` = (SELECT `bug_status_internal`.`id`
                                                                FROM `mantis_bug_history_table` `bug_status_internal`
                                                                WHERE `bug`.`id` = `bug_status_internal`.`bug_id`
                                                                  AND `bug_status_internal`.`field_name` = 'status'
                                                                  AND `bug_status_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                ORDER BY `bug_status_internal`.`date_modified` DESC
                                                                LIMIT 1)
                                LEFT JOIN `mantis_bug_history_table` `bug_project`
                                       ON `bug_project`.`id` = (SELECT `bug_project_internal`.`id`
                                                                FROM `mantis_bug_history_table` `bug_project_internal`
                                                                WHERE `bug`.`id` = `bug_project_internal`.`bug_id`
                                                                  AND `bug_project_internal`.`field_name` = 'project_id'
                                                                  AND `bug_project_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                ORDER BY `bug_project_internal`.`date_modified` DESC
                                                                LIMIT 1)
                                LEFT JOIN `mantis_bug_history_table` `bug_handler`
                                       ON `bug_handler`.`id` = (SELECT `bug_handler_internal`.`id`
                                                                FROM `mantis_bug_history_table` `bug_handler_internal`
                                                                WHERE `bug`.`id` = `bug_handler_internal`.`bug_id`
                                                                  AND `bug_handler_internal`.`field_name` = 'handler_id'
                                                                  AND `bug_handler_internal`.`date_modified` < UNIX_TIMESTAMP('{$date}' + INTERVAL 1 DAY)
                                                                ORDER BY `bug_handler_internal`.`date_modified` DESC
                                                                LIMIT 1)) `bug`
                        INNER JOIN `mantis_project_table` PROJECT
                                ON `bug`.`project_id` = PROJECT.`id`
                               AND PROJECT.`name` LIKE 'CLM - %'
                         LEFT JOIN `mantis_user_table` `handler`
                                ON `bug`.`handler_id` = `handler`.`id`
                       WHERE `bug`.`status` IN ({$pendingStatuses})
                       GROUP BY `handler`.`email`, `bug`.`status`, `bug`.`gop`
                       ORDER BY `handler_realname`)";
    }

    $statusQuery = db_query($statusSql);
    $counts = array();
    while ($queryResult = db_fetch_array($statusQuery))
    {
        if (!isset($counts[$queryResult['handler_realname']]))
        {
            $counts[$queryResult['handler_realname']] = array();
        }
        if (!isset($counts[$queryResult['handler_realname']][$queryResult['status']]))
        {
            $counts[$queryResult['handler_realname']][$queryResult['status']] = array('gop' => 0, 'non-gop' => 0);
        }
        $counts[$queryResult['handler_realname']][$queryResult['status']][$queryResult['gop']] = $queryResult['count'];
    }
    return $counts;
}

function getIssueCounts()
{
    global $STATUS_LIST, $PENDING_LIST;
    $pendingStatuses = implode(',', $PENDING_LIST);
    $statusSql = "(SELECT COUNT(BUG.`status`) AS 'count', 'A' AS 'label', YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) AS 'year'
                   FROM `mantis_bug_table` BUG
                    INNER JOIN `mantis_project_table` PROJECT
                            ON BUG.`project_id` = PROJECT.`id`
                           AND PROJECT.`name` LIKE 'CLM - %'
                   WHERE BUG.`status` IN ({$pendingStatuses})
                     AND YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) = YEAR(NOW()))
                  UNION ALL
                  (SELECT COUNT(BUG.`status`) AS 'count', 'D' AS 'label', YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) AS 'year'
                   FROM `mantis_bug_table` BUG
                    INNER JOIN `mantis_project_table` PROJECT
                            ON BUG.`project_id` = PROJECT.`id`
                           AND PROJECT.`name` LIKE 'CLM - %'
                   WHERE BUG.`summary` NOT LIKE '%GOP%'
                     AND BUG.`status` IN ({$pendingStatuses})
                     AND YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) = YEAR(NOW()))
                  UNION ALL
                  (SELECT COUNT(BUG.`status`) AS 'count', 'B' AS 'label', YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) AS 'year'
                   FROM `mantis_bug_table` BUG
                    INNER JOIN `mantis_project_table` PROJECT
                            ON BUG.`project_id` = PROJECT.`id`
                           AND PROJECT.`name` LIKE 'CLM - %'
                   WHERE YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) = YEAR(NOW())
                     AND (BUG.`summary` NOT LIKE '%GOP%' OR
                          (BUG.`status` NOT IN (13, 90) AND
                           BUG.`summary` LIKE '%GOP%')))
                  UNION ALL
                  (SELECT COUNT(BUG.`status`) AS 'count', 'E' AS 'label', YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) AS 'year'
                   FROM `mantis_bug_table` BUG
                    INNER JOIN `mantis_project_table` PROJECT
                            ON BUG.`project_id` = PROJECT.`id`
                           AND PROJECT.`name` LIKE 'CLM - %'
                   WHERE YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) = YEAR(NOW())
                     AND (BUG.`summary` NOT LIKE '%GOP%' OR
                          (BUG.`status` IN (11, 12) AND
                           BUG.`summary` LIKE '%GOP%')))
                  UNION ALL
                  (SELECT COUNT(BUG.`status`) AS 'count', 'A' AS 'label', YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) AS 'year'
                   FROM `mantis_bug_table` BUG
                    INNER JOIN `mantis_project_table` PROJECT
                            ON BUG.`project_id` = PROJECT.`id`
                           AND PROJECT.`name` LIKE 'CLM - %'
                   WHERE BUG.`status` IN ({$pendingStatuses})
                     AND YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) = (YEAR(NOW())) - 1)
                  UNION ALL
                  (SELECT COUNT(BUG.`status`) AS 'count', 'D' AS 'label', YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) AS 'year'
                   FROM `mantis_bug_table` BUG
                    INNER JOIN `mantis_project_table` PROJECT
                            ON BUG.`project_id` = PROJECT.`id`
                           AND PROJECT.`name` LIKE 'CLM - %'
                   WHERE BUG.`summary` NOT LIKE '%GOP%'
                     AND BUG.`status` IN ({$pendingStatuses})
                     AND YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) = (YEAR(NOW())) - 1)
                  UNION ALL
                  (SELECT COUNT(BUG.`status`) AS 'count', 'B' AS 'label', YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) AS 'year'
                   FROM `mantis_bug_table` BUG
                    INNER JOIN `mantis_project_table` PROJECT
                            ON BUG.`project_id` = PROJECT.`id`
                           AND PROJECT.`name` LIKE 'CLM - %'
                   WHERE YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) = (YEAR(NOW()) - 1)
                     AND (BUG.`summary` NOT LIKE '%GOP%' OR
                          (BUG.`status` NOT IN (13, 90) AND
                           BUG.`summary` LIKE '%GOP%')))
                  UNION ALL
                  (SELECT COUNT(BUG.`status`) AS 'count', 'E' AS 'label', YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) AS 'year'
                   FROM `mantis_bug_table` BUG
                    INNER JOIN `mantis_project_table` PROJECT
                            ON BUG.`project_id` = PROJECT.`id`
                           AND PROJECT.`name` LIKE 'CLM - %'
                   WHERE YEAR(FROM_UNIXTIME(BUG.`date_submitted`)) = (YEAR(NOW()) - 1)
                     AND (BUG.`summary` NOT LIKE '%GOP%' OR
                          (BUG.`status` IN (11, 12) AND
                           BUG.`summary` LIKE '%GOP%')))";
    $statusQuery = db_query($statusSql);

    $lastYear = date("Y", strtotime("-1 year"));
    $thisYear = date("Y");
    $counts = array($lastYear => array(), $thisYear => array());
    while ($queryResult = db_fetch_array($statusQuery))
    {
        $counts[$queryResult['year']][$queryResult['label']] = $queryResult['count'];
    }
    $counts[$lastYear]['C'] = round($counts[$lastYear]['A'] * 100 / $counts[$lastYear]['B'], 2) . '%';
    $counts[$lastYear]['F'] = round($counts[$lastYear]['D'] * 100 / $counts[$lastYear]['E'], 2) . '%';
    $counts[$thisYear]['C'] = round($counts[$thisYear]['A'] * 100 / $counts[$thisYear]['B'], 2) . '%';
    $counts[$thisYear]['F'] = round($counts[$thisYear]['D'] * 100 / $counts[$thisYear]['E'], 2) . '%';

    return $counts;
}

function getMonitoredIssues()
{
    global $NEVER_OVERTIME_STATUS_LIST, $STATUS_LIST;
    $results = array();
    $neverOvertimeStatuses = implode(',', $NEVER_OVERTIME_STATUS_LIST);

    $pedingOnlyStatement = "";
    if (isset($_GET['pending_only']))
    {
        $pedingOnlyStatement = " AND BUG.`status` NOT IN ({$neverOvertimeStatuses})";
    }
    if (isset($_GET['no_pending']))
    {
        $pedingOnlyStatement = " AND BUG.`status` IN ({$neverOvertimeStatuses})";
    }

    foreach ($STATUS_LIST as $code => $value)
    {
        $statusSql = "SELECT DISTINCT
                         BUG.`id`,
                         BUG.`summary`,
                         BUG.`status`,
                         MONITORER.`email` AS 'monitorer_email',
                         MONITORER.`realname` AS 'monitorer_realname',
                         HANDLER.`email` AS 'handler_email',
                         HANDLER.`realname` AS 'handler_realname',
                         DATE_FORMAT(FROM_UNIXTIME(BUG.`last_updated`), '%m/%d/%Y %H:%i:%s') as 'last_updated',
                         COUNT_WORKING_DAYS(FROM_UNIXTIME(BUG.`date_submitted`), NOW()) as 'working_days'
                      FROM `mantis_bug_table` BUG
                        INNER JOIN `mantis_project_table` PROJECT
                                ON BUG.`project_id` = PROJECT.`id`
                               AND PROJECT.`name` LIKE 'CLM - %'
                        INNER JOIN `mantis_bug_monitor_table` MONITORED
                                ON BUG.`id` = MONITORED.`bug_id`
                        INNER JOIN `mantis_user_table` MONITORER
                                ON MONITORED.`user_id` = MONITORER.`id`
                         LEFT JOIN `mantis_user_table` HANDLER
                                ON BUG.`handler_id` = HANDLER.`id`
                      WHERE BUG.`status` = {$code} {$pedingOnlyStatement}
                        AND BUG.`summary` NOT LIKE '%GOP%'
                        AND MONITORER.`email` NOT LIKE '%dai-ichi-life.com.vn'
                      GROUP BY BUG.`id`, MONITORER.`email`
                      ORDER BY `monitorer_realname` ASC, `working_days` DESC";
        $statusQuery = db_query($statusSql);
        while ($queryResult = db_fetch_array($statusQuery))
        {
            if (!isset($results[$queryResult['monitorer_realname']]))
            {
                $results[$queryResult['monitorer_realname']] = array($queryResult['monitorer_email'] => array());
            }
            if (!isset($results[$queryResult['monitorer_realname']][$queryResult['monitorer_email']][$queryResult['status']]))
            {
                $results[$queryResult['monitorer_realname']][$queryResult['monitorer_email']][$queryResult['status']] = array();
            }
            $results[$queryResult['monitorer_realname']][$queryResult['monitorer_email']][$queryResult['status']][] = array
            (
                'id' => $queryResult['id'],
                'summary' => $queryResult['summary'],
                'status' => $queryResult['status'],
                'last_updated' => $queryResult['last_updated'],
                'working_days' => $queryResult['working_days'],
                'monitorer_realname' => $queryResult['monitorer_realname'],
                'monitorer_email' => $queryResult['monitorer_email'],
                'handler_realname' => $queryResult['handler_realname'],
                'handler_email' => $queryResult['handler_email']
            );
        }
    }
    return $results;
}

function displayMonitoredIssues($monitoredIssues)
{
    $content = '';
    if (count($monitoredIssues) > 0)
    {
        $content .= "<h1>Monitored Issues</h1>";
        foreach ($monitoredIssues as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content .= strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray;color: white"><td>Link</td><td>Summary</td><td>Last Updated</td><td>Overdue Days</td><td>Assigned To</td><td>Current Status</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getMonitoredIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';
            }
        }
    }
    echo $content;
}

function sendMonitoredEmails($mailer, $MonitoredIssues)
{
    if (count($MonitoredIssues) > 0)
    {
        foreach ($MonitoredIssues as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content = strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray;color: white"><td>Link</td><td>Summary</td><td>Last Updated</td><td>Overdue Days</td><td>Assigned To</td><td>Current Status</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getMonitoredIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';

                $subject =  "Monitored Issues of {$realname}";
                sendMail($mailer, array($realname => $emailAddress), $subject, $content);
            }
        }
    }
}

function getMonitoredIssuesContent($code, $item)
{
    global $LINK, $STATUS_LIST, $STATUS_COLOR_LIST;
    return "<tr style=\"background: #{$STATUS_COLOR_LIST[$code]}\">
                <td><a href=\"{$LINK}{$item['id']}\">Issue {$item['id']}</a></td>
                <td>{$item['summary']}</td>
                <td>{$item['last_updated']}</td>
                <td>{$item['working_days']}</td>
                <td>{$item['handler_realname']}</td>
                <td>{$STATUS_LIST[$code]}</td>
            </tr>";
}

function sendOverdueEmails($mailer, $overdues)
{
    if (count($overdues) > 0)
    {
        foreach ($overdues as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content = strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray;color: white"><td>Link</td><td>Summary</td><td>Last Updated</td><td>Overdue Days</td><td>Last Status Changed</td><td>Current Status</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getOverdueIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';

                $subject =  "Overdue Issues of {$realname}";
                sendMail($mailer, array($realname => $emailAddress), $subject, $content);
            }
        }
    }
}

function displayOverdueIssues($overdues)
{
    $content = '';
    if (count($overdues) > 0)
    {
        $content .= "<h1>Overdue Issues</h1>";
        foreach ($overdues as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content .= strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray;color: white"><td>Link</td><td>Summary</td><td>Last Updated</td><td>Overdue Days</td><td>Last Status Changed</td><td>Current Status</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getOverdueIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';
            }
        }
    }
    echo $content;
}

function getOverdueIssuesContent($code, $item)
{
    global $LINK, $STATUS_LIST, $STATUS_COLOR_LIST;
    return "<tr style=\"background: #{$STATUS_COLOR_LIST[$code]}\">
                <td><a href=\"{$LINK}{$item['id']}\">Issue {$item['id']}</a></td>
                <td>{$item['summary']}</td>
                <td>{$item['last_updated']}</td>
                <td>{$item['working_days']}</td>
                <td>{$item['last_status_changed']}</td>
                <td>{$STATUS_LIST[$code]}</td>
            </tr>";
}

function sendOvertimeEmails($mailer, $overtimes)
{
    global $DAY_MAX;
    if (count($overtimes) > 0)
    {
        foreach ($overtimes as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content = strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray; color: white"><td>Link</td><td>Summary</td><td>Last Updated</td><td>Overdue Days</td><td>Last Status Changed</td><td>Current Status</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getOvertimeIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';

                $subject =  "Over {$DAY_MAX} days Issues of {$realname}";
                sendMail($mailer, array($realname => $emailAddress), $subject, $content);
            }
        }
    }
}

function displayOvertimeIssues($overtimes)
{
    global $DAY_MAX;
    $content = '';
    if (count($overtimes) > 0)
    {
        $content .= "<h1>Over {$DAY_MAX} days Issues</h1>";
        foreach ($overtimes as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content .= strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray; color: white"><td>Link</td><td>Summary</td><td>Last Updated</td><td>Overdue Days</td><td>Date Submitted</td><td>Current Status</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getOvertimeIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';
            }
        }
    }
    echo $content;
}

function getOvertimeIssuesContent($code, $item)
{
    global $LINK, $STATUS_LIST, $STATUS_COLOR_LIST;
    return "<tr style=\"background: #{$STATUS_COLOR_LIST[$code]}\">
                <td><a href=\"{$LINK}{$item['id']}\">Issue {$item['id']}</a></td>
                <td>{$item['summary']}</td>
                <td>{$item['last_updated']}</td>
                <td>{$item['working_days']}</td>
                <td>{$item['date_submitted']}</td>
                <td>{$STATUS_LIST[$code]}</td>
            </tr>";
}

function displayAllPendingIssues($pendings)
{
    $content = '';
    if (count($pendings) > 0)
    {
        $content .= "<h1>Pending Issues</h1>";
        foreach ($pendings as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content .= strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray;color: white"><td>Link</td><td>Claim No</td><td>Summary</td><td>Date Submitted</td><td>Last Updated</td><td>Days</td><td>Current Status</td><td>Reason For Pending</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getAllPendingIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';
            }
        }
    }
    echo $content;
}

function getAllPendingIssuesContent($code, $item)
{
    global $LINK, $STATUS_LIST, $STATUS_COLOR_LIST;
    return "<tr style=\"background: #{$STATUS_COLOR_LIST[$code]}\">
                <td><a href=\"{$LINK}{$item['id']}\">Issue {$item['id']}</a></td>
                <td>{$item['claim_no']}</td>
                <td>{$item['summary']}</td>
                <td>{$item['date_submitted']}</td>
                <td>{$item['last_updated']}</td>
                <td>{$item['working_days']}</td>
                <td>{$STATUS_LIST[$code]}</td>
                <td>{$item['reason']}</td>
            </tr>";
}

function sendPendingEmails($mailer, $pendings)
{
    global $PENDING_LIMIT;
    if (count($pendings) > 0)
    {
        foreach ($pendings as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content = strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray;color: white"><td>Link</td><td>Summary</td><td>Last Updated</td><td>Overdue Days</td><td>Last Status Changed</td><td>Current Status</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getPendingIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';

                $subject =  "Pending Issues (Over {$PENDING_LIMIT} days) of {$realname}";
                sendMail($mailer, array($realname => $emailAddress), $subject, $content);
            }
        }
    }
}

function displayPendingIssues($pendings, $date = null)
{
    global $PENDING_LIMIT;
    $content = '';
    if (count($pendings) > 0)
    {
        $today = date('d/m/Y');
        $header = is_null($date) ? "Pending Issues" : ($today === $date ? "Pending Issues at {$date}" : "Pending Issues at {$date} 23:59:59");
        $content .= "<h1>{$header} (Over {$PENDING_LIMIT} days)</h1>";
        foreach ($pendings as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content .= strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray;color: white"><td>Link</td><td>Summary</td><td>Last Updated</td><td>Overdue Days</td><td>Last Status Changed</td><td>Current Status</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getPendingIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';
            }
        }
    }
    echo $content;
}

function getPendingIssuesContent($code, $item)
{
    global $LINK, $STATUS_LIST, $STATUS_COLOR_LIST;
    return "<tr style=\"background: #{$STATUS_COLOR_LIST[$code]}\">
                <td><a href=\"{$LINK}{$item['id']}\">Issue {$item['id']}</a></td>
                <td>{$item['summary']}</td>
                <td>{$item['last_updated']}</td>
                <td>{$item['working_days']}</td>
                <td>{$item['last_status_changed']}</td>
                <td>{$STATUS_LIST[$code]}</td>
            </tr>";
}

function sendPendingInfoReceivedEmails($mailer, $pendings)
{
    global $INFO_RECEIVED_LIMIT;
    if (count($pendings) > 0)
    {
        foreach ($pendings as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content = strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray;color: white"><td>Link</td><td>Summary</td><td>Last Updated</td><td>Overdue Days</td><td>Last Status Changed</td><td>Current Status</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getPendingInfoReceivedIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';

                $subject =  "Pending Info Received Issues (Over {$INFO_RECEIVED_LIMIT} days) of {$realname}";
                sendMail($mailer, array($realname => $emailAddress), $subject, $content);
            }
        }
    }
}

function displayPendingInfoReceivedIssues($pendings, $date = null)
{
    global $INFO_RECEIVED_LIMIT;
    $content = '';
    if (count($pendings) > 0)
    {
        $today = date('d/m/Y');
        $header = is_null($date) ? "Pending Info Received Issues" : ($today === $date ? "Pending Info Received Issues at {$date}" : "Pending Info Received Issues at {$date} 23:59:59");
        $content .= "<h1>{$header} (Over {$INFO_RECEIVED_LIMIT} days)</h1>";
        foreach ($pendings as $realname => $email)
        {
            $realname = str_replace(' CLM', '', $realname);
            foreach ($email as $emailAddress => $emailItems)
            {
                $content .= strlen($realname) > 0 ? "<h2>{$realname}</h2>" : "<h2>Un-Assigned</h2>";
                $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black"><tr style="background-color: gray;color: white"><td>Link</td><td>Summary</td><td>Last Updated</td><td>Overdue Days</td><td>Last Status Changed</td><td>Current Status</td></tr>';
                foreach ($emailItems as $code => $items)
                {
                    foreach ($items as $item)
                    {
                        $content .= getPendingInfoReceivedIssuesContent($code, $item);
                    }
                }
                $content .= '</table><br/>';
            }
        }
    }
    echo $content;
}

function getPendingInfoReceivedIssuesContent($code, $item)
{
    global $LINK, $STATUS_LIST, $STATUS_COLOR_LIST;
    return "<tr style=\"background: #{$STATUS_COLOR_LIST[$code]}\">
                <td><a href=\"{$LINK}{$item['id']}\">Issue {$item['id']}</a></td>
                <td>{$item['summary']}</td>
                <td>{$item['last_updated']}</td>
                <td>{$item['working_days']}</td>
                <td>{$item['last_status_changed']}</td>
                <td>{$STATUS_LIST[$code]}</td>
            </tr>";
}

function sendPendingIssueCountsEmail($mailer, $pendingContent, $content)
{
    $subject = "Pending Status Report";
    sendMail($mailer, array($FALLBACK_EMAIL['name'] => $FALLBACK_EMAIL['email']), $subject, "<h1>Pending Status Report</h1>{$pendingContent}{$content}");
}

function displayPendingIssueCounts($pendingContent, $content = '', $date = null)
{
    $today = date('d/m/Y');
    $header = is_null($date) ? "Pending Status Report" : ($today === $date ? "Pending Status Report at {$date}" : "Pending Status Report at {$date} 23:59:59");
    echo "<h1>{$header}</h1>{$pendingContent}{$content}";
}

function getPendingIssueCountsContent($counts)
{
    global $STATUS_LIST, $STATUS_COLOR_LIST, $PENDING_LIST;

    $statusLabels = array();
    $statusTotals = array();
    $statusStyles = array();
    foreach ($PENDING_LIST as $status)
    {
        $statusLabels[] = "<th style=\"border-color: black; background-color: #{$STATUS_COLOR_LIST[$status]}; text-align: center\" class=\"class-{$status}-non-gop\">{$STATUS_LIST[$status]}</th><th style=\"background-color: #{$STATUS_COLOR_LIST[$status]}; text-align: center\" class=\"class-{$status}-gop\">{$STATUS_LIST[$status]}-gop</th>";
        $statusTotals[$status] = array('non-gop' => 0, 'gop' => 0);
        $statusStyles[$status] = array('non-gop' => array(), 'gop' => array());
    }
    $header = '<tr style="font-size: 20px"><th>Full Name</th>' . implode('', $statusLabels) . '<th>User Total (Non GOP)</th><th>User Total (GOP)</th><th>User Total (All)</th></tr>';

    $rows = array();
    $grandTotals = array('gop' => 0, 'non-gop' => 0, 'all' => 0);
    foreach ($counts as $name => $statuses)
    {
        $name = str_replace(' CLM', '', $name);
        $userTotal = array('gop' => 0, 'non-gop' => 0, 'all' => 0);
        foreach ($statuses as $userStatus => $gops)
        {
            foreach ($gops as $gop => $value)
            {
                $statusTotals[$userStatus][$gop] += $value;
                $userTotal[$gop] += $value;
                $userTotal['all'] += $value;
                $grandTotals[$gop] += $value;
                $grandTotals['all'] += $value;
            }
        }

        $statusValues = array();
        foreach ($PENDING_LIST as $status)
        {
            $gopValue = $statuses[$status]['gop'] == 0 ? '' : $statuses[$status]['gop'];
            $nonGopValue = $statuses[$status]['non-gop'] == 0 ? '' : $statuses[$status]['non-gop'];
            $statusValues[] = "<td style=\"border-color: black; background-color: #{$STATUS_COLOR_LIST[$status]}; text-align: center\" class=\"class-{$status}-non-gop\">{$nonGopValue}</td><td style=\"background-color: #{$STATUS_COLOR_LIST[$status]}; text-align: center\" class=\"class-{$status}-gop\">{$gopValue}</td>";
        }

        if ($userTotal['all'] > 0)
        {
            $gopValue = $userTotal['gop'] == 0 ? '' : $userTotal['gop'];
            $nonGopValue = $userTotal['non-gop'] == 0 ? '' : $userTotal['non-gop'];
            $rows[] = "<tr><td style=\"border-color: black; text-align: right\">{$name}</td>" . implode('', $statusValues) . "<th style=\"border-color: black; color: red\">{$nonGopValue}</th><th style=\"border-color: black; color: red\">{$gopValue}</th><th style=\"border-color: black; color: red\">{$userTotal['all']}</th></tr>";
        }
    }

    $footer = '<tr style="border-color: black; color: red"><th style="font-size: 20px; color: black">Grand Total</th>';
    foreach ($statusTotals as $status => $gops)
    {
        foreach ($gops as $gop => $total)
        {
            $footer .= "<th style=\"border-color: black; background-color: #{$STATUS_COLOR_LIST[$status]}; text-align: center\" class=\"class-{$status}-{$gop}\">{$total}</th>";
            $statusStyles[$status][$gop][] = $total == 0 ? "display: none" : null;
        }
    }
    $gopValue = $grandTotals['gop'] == 0 ? '' : $grandTotals['gop'];
    $nonGopValue = $grandTotals['non-gop'] == 0 ? '' : $grandTotals['non-gop'];
    $footer .= "<th style=\"border-color: black; font-size: 20px\">{$nonGopValue}</th><th style=\"border-color: black; font-size: 20px\">{$gopValue}</th><th style=\"border-color: black; font-size: 25px\">{$grandTotals['all']}</th></tr>";

    foreach ($statusStyles as $status => $gops)
    {
        foreach ($gops as  $gop => $styles)
        {
            $statusStyleStrings[] = ".class-{$status}-{$gop} {" . implode('; ', $styles) . "}";
        }
    }

    return "<style>" . implode(' ', $statusStyleStrings) . "</style><table style=\"border-collapse: collapse\" style=\"text-align: center\" border=\"1px\">{$header}" . implode('', $rows) . "{$footer}</table>";
}

function getIssueCountsContent($counts)
{
    $lastYear = date("Y", strtotime("-1 year"));
    $thisYear = date("Y");
    return "<h1>Percent Status Report {$thisYear}</h1>
            <table style=\"border-collapse: collapse; text-align: center\" border=\"1\">
                <tr><th></th><th>Pending</th><th>Total</th><th>Percent</th></tr>
                <tr><th>Individual + GOP</th><td>{$counts[$thisYear]['A']}</td><td>{$counts[$thisYear]['B']}</td><td>{$counts[$thisYear]['C']}</td></tr>
                <tr><th>Individual Only</th><td>{$counts[$thisYear]['D']}</td><td>{$counts[$thisYear]['E']}</td><td>{$counts[$thisYear]['F']}</td></tr>
            </table>
            <h1>Percent Status Report {$lastYear}</h1>
            <table style=\"border-collapse: collapse; text-align: center\" border=\"1\">
                <tr><th></th><th>Pending</th><th>Total</th><th>Percent</th></tr>
                <tr><th>Individual + GOP</th><td>{$counts[$lastYear]['A']}</td><td>{$counts[$lastYear]['B']}</td><td>{$counts[$lastYear]['C']}</td></tr>
                <tr><th>Individual Only</th><td>{$counts[$lastYear]['D']}</td><td>{$counts[$lastYear]['E']}</td><td>{$counts[$lastYear]['F']}</td></tr>
            </table>";
}

function displayPendingIssueCountsChart($pendingChart, $chart)
{
    echo "<h1>Pending Status Report</h1>
          <script>
                window.onload = function()
                {
                    {$pendingChart}
                    {$chart}
                }
          </script>
          <div id=\"pending-chart\" style=\"height: 700px; width: 100%;\"></div>
          <div id=\"this-year-chart-ig\" style=\"height: 700px; width: 40%;\"></div>
          <div id=\"this-year-chart-io\" style=\"height: 700px; width: 40%;\"></div>
          <div id=\"last-year-chart-ig\" style=\"height: 700px; width: 40%;\"></div>
          <div id=\"last-year-chart-io\" style=\"height: 700px; width: 40%;\"></div>
          <script src=\"https://canvasjs.com/assets/script/canvasjs.min.js\"></script>
         ";
}

function getPendingIssueCountsChart($counts)
{
    global $STATUS_LIST, $STATUS_COLOR_LIST, $PENDING_LIST;

    $data = array();
    foreach ($counts as $name => $statuses)
    {
        $userData = array();
        foreach ($PENDING_LIST as $status)
        {
            $userData[] = array('label' => $STATUS_LIST[$status], 'y' => $statuses[$status]['non-gop']);
            $userData[] = array('label' => "{$STATUS_LIST[$status]}-gop", 'y' => $statuses[$status]['gop']);
        }

        $data[] = array
        (
            'type' => "stackedColumn",
            'name' => str_replace(' CLM', '', $name),
            'showInLegend' => true,
            'dataPoints' => $userData
        );
    }

    return 'var pendingChart = new CanvasJS.Chart("pending-chart",
            {
                title: { text: "Pending Status" },
                theme: "light2",
                animationEnabled: true,
                toolTip: { shared: true, reversed: true },
                axisY: { title: "Issues" },
                legend: { cursor: "pointer", itemclick: toggleDataSeries },
                data: ' . json_encode($data, JSON_NUMERIC_CHECK) . '
            });

            pendingChart.render();

            function toggleDataSeries(e)
            {
                if (typeof (e.dataSeries.visible) === "undefined" || e.dataSeries.visible) { e.dataSeries.visible = false; }
                else { e.dataSeries.visible = true; }
                e.chart.render();
            }';
}

function getIssueCountsChart($counts)
{
    $lastYear = date("Y", strtotime("-1 year"));
    $thisYear = date("Y");

    $lastYearIgData = array(array
    (
        'type' => "doughnut",
        'indexLabel' => "{symbol} - {y}",
        'showInLegend' => true,
        'legendText' => "{label} : {y}",
        'dataPoints' => array
        (
            array("label" => "Pending", "symbol" => "Pending", "y" => $counts[$lastYear]['A']),
            array("label" => "Completed", "symbol" => "Completed", "y" => $counts[$lastYear]['B'] - $counts[$lastYear]['A'])
        )
    ));

    $thisYearIgData = array(array
    (
        'type' => "doughnut",
        'indexLabel' => "{symbol} - {y}",
        'showInLegend' => true,
        'legendText' => "{label} : {y}",
        'dataPoints' => array
        (
            array("label" => "Pending", "symbol" => "Pending", "y" => $counts[$thisYear]['A']),
            array("label" => "Completed", "symbol" => "Completed", "y" => $counts[$thisYear]['B'] - $counts[$thisYear]['A'])
        )
    ));

    $lastYearIoData = array(array
    (
        'type' => "doughnut",
        'indexLabel' => "{symbol} - {y}",
        'showInLegend' => true,
        'legendText' => "{label} : {y}",
        'dataPoints' => array
        (
            array("label"=>"Pending", "symbol" => "Pending", "y" => $counts[$lastYear]['D']),
            array("label"=>"Completed", "symbol" => "Completed", "y" => $counts[$lastYear]['E'] - $counts[$lastYear]['D'])
        )
    ));

    $thisYearIoData = array(array
    (
        'type' => "doughnut",
        'indexLabel' => "{symbol} - {y}",
        'showInLegend' => true,
        'legendText' => "{label} : {y}",
        'dataPoints' => array
        (
            array("label"=>"Pending", "symbol" => "Pending", "y" => $counts[$thisYear]['D']),
            array("label"=>"Completed", "symbol" => "Completed", "y" => $counts[$thisYear]['E'] - $counts[$thisYear]['D'])
        )
    ));

    return 'var lastYearChartIG = new CanvasJS.Chart("last-year-chart-ig",
            {
                title: { text: "Individual + GOP Percent Status ' . $lastYear . '" },
                theme: "light2",
                animationEnabled: true,
                data: ' . json_encode($lastYearIgData, JSON_NUMERIC_CHECK) . '
            });
            lastYearChartIG.render();

            var thisYearChartIG = new CanvasJS.Chart("this-year-chart-ig",
            {
                title: { text: "Individual + GOP Percent Status ' . $thisYear . '" },
                theme: "light2",
                animationEnabled: true,
                data: ' . json_encode($thisYearIgData, JSON_NUMERIC_CHECK) . '
            });
            thisYearChartIG.render();

            var lastYearChartIO = new CanvasJS.Chart("last-year-chart-io",
            {
                title: { text: "Individual Only Percent Status ' . $lastYear . '" },
                theme: "light2",
                animationEnabled: true,
                data: ' . json_encode($lastYearIoData, JSON_NUMERIC_CHECK) . '
            });
            lastYearChartIO.render();

            var thisYearChartIO = new CanvasJS.Chart("this-year-chart-io",
            {
                title: { text: "Individual Only Percent Status ' . $thisYear . '" },
                theme: "light2",
                animationEnabled: true,
                data: ' . json_encode($thisYearIoData, JSON_NUMERIC_CHECK) . '
            });
            thisYearChartIO.render();

            function toggleDataSeries(e)
            {
                if (typeof (e.dataSeries.visible) === "undefined" || e.dataSeries.visible) { e.dataSeries.visible = false; }
                else { e.dataSeries.visible = true; }
                e.chart.render();
            }';
}
