<?php
require_once __DIR__ . '/phpmailer/phpmailer/PHPMailerAutoload.php';

$FALLBACK_EMAIL = array
(
    'name' => 'Hương Xã',
    'email' => 'huongxa@pacificcross.com.vn'
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
    $mailer->SetFrom('admin@pacificcross.vn', 'DLVN TAT Checker');
    $mailer->AddReplyTo('admin@pacificcross.vn', 'DLVN TAT Checker');

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

function getLastNotedIssues($from, $to, $minify = false)
{
    $results = array();
    $sql = "SELECT * FROM
            (
                SELECT DISTINCT
                    `bug`.`id` `bug_id`,
                    `bug`.`status`,
                    `note_text`.`call_date`,
                    DATE_FORMAT(`note_text`.`call_date`, '%d') `day_of_month`,
                    CASE DAYOFWEEK(`note_text`.`call_date`)
                        WHEN 1 THEN 'Sun'
                        WHEN 2 THEN 'Mon'
                        WHEN 3 THEN 'Tue'
                        WHEN 4 THEN 'Wed'
                        WHEN 5 THEN 'Thu'
                        WHEN 6 THEN 'Fri'
                        WHEN 7 THEN 'Sat'
                    END `day_of_week`,
                    `note_text`.`call_time`,
                    CASE WHEN `note_text`.`call_time` BETWEEN '08:00' AND '17:00' THEN '08:00 - 17:00' ELSE '17:00 - 08:00' END `time_range`,
                    CASE WHEN `note_text`.`call_time` BETWEEN '08:00' AND '17:00' THEN 'CS' ELSE '' END `hotline_keeper`,
                    TIME_FORMAT(`note_text`.`call_time`, '%H:%i') `time`,
                    `note_text`.`tel_no` `tel_no`,
                    `note_text`.`note` `content`,
                    `note_reporter`.`realname` `handled_by`
                FROM `mantis_bug_table` `bug`
                    JOIN `mantis_project_table` `project`
                      ON `bug`.`project_id` = `project`.`id`
                     AND `project`.`name` IN ('CS - Call Log')
                    JOIN `mantis_bugnote_table` `note`
                      ON `note`.`bug_id` = `bug`.`id`
                    JOIN `mantis_bugnote_text_table` `note_text`
                      ON `note`.`bugnote_text_id` = `note_text`.`id`
                    JOIN `mantis_user_table` `note_reporter`
                      ON `note`.`reporter_id` = `note_reporter`.`id`
                WHERE `note_text`.`call_date` BETWEEN '{$from}' AND '{$to}'
                UNION ALL
                SELECT DISTINCT
                    `bug`.`id` `bug_id`,
                    `bug`.`status`,
                    DATE_FORMAT(FROM_UNIXTIME(`receive_date`.`value`), '%Y-%m-%d') `call_date`,
                    DATE_FORMAT(FROM_UNIXTIME(`receive_date`.`value`), '%d') `day_of_month`,
                    CASE DAYOFWEEK(FROM_UNIXTIME(`receive_date`.`value`))
                        WHEN 1 THEN 'Sun'
                        WHEN 2 THEN 'Mon'
                        WHEN 3 THEN 'Tue'
                        WHEN 4 THEN 'Wed'
                        WHEN 5 THEN 'Thu'
                        WHEN 6 THEN 'Fri'
                        WHEN 7 THEN 'Sat'
                    END `day_of_week`,
                    `call_time`.`value` `call_time`,
                    CASE WHEN `call_time`.`value` BETWEEN '08:00' AND '17:00' THEN '08:00 - 17:00' ELSE '17:00 - 08:00' END `time_range`,
                    CASE WHEN `call_time`.`value` BETWEEN '08:00' AND '17:00' THEN 'CS' ELSE '' END `hotline_keeper`,
                    `call_time`.`value` `time`,
                    `tel_no`.`value` `tel_no`,
                    `bug_text`.`description` `content`,
                    `bug_reporter`.`realname` `handled_by`
                FROM `mantis_bug_table` `bug`
                    JOIN `mantis_bug_text_table` `bug_text`
                      ON `bug`.`bug_text_id` = `bug_text`.`id`
                    JOIN `mantis_project_table` `project`
                      ON `bug`.`project_id` = `project`.`id`
                     AND `project`.`name` IN ('CS - Call Log')
                    JOIN `mantis_custom_field_string_table` `receive_date`
                      ON `receive_date`.`bug_id` = `bug`.`id`
                     AND `receive_date`.`field_id` = 2
                    JOIN `mantis_custom_field_string_table` `call_time`
                      ON `call_time`.`bug_id` = `bug`.`id`
                     AND `call_time`.`field_id` = 21
                    JOIN `mantis_custom_field_string_table` `tel_no`
                      ON `tel_no`.`bug_id` = `bug`.`id`
                     AND `tel_no`.`field_id` = 22
                    JOIN `mantis_user_table` `bug_reporter`
                      ON `bug`.`reporter_id` = `bug_reporter`.`id`
                WHERE FROM_UNIXTIME(`receive_date`.`value`) BETWEEN '{$from}' AND '{$to}'
            ) `data`
            ORDER BY `call_date`, `call_time`";
    $query = db_query($sql);
    while ($result = db_fetch_array($query))
    {
        if (!isset($results[$result['bug_id']]))
        {
            $results[$result[$result['bug_id']]] = array();
        }
        $results[$result['bug_id']][] = array
        (
            'bug_id' => $result['bug_id'],
            'status' => $result['status'],
            'day_of_month' => $result['day_of_month'],
            'day_of_week' => $result['day_of_week'],
            'time_range' => $result['time_range'],
            'hotline_keeper' => $result['hotline_keeper'],
            'time' => $result['time'],
            'tel_no' => $result['tel_no'],
            'content' => str_replace("\n", $minify ? "|" : "<br />", $result['content']),
            'handled_by' => $result['handled_by']
        );
    }
    return $results;
}

function displayLastNotedIssues($items)
{
    $content = '';
    if (count($items) > 0)
    {
        $content .= '<table style="border-collapse: collapse" width="100%" border="1px solid black">
                        <tr style="background-color: gray;color: white; text-align: center;">
                            <td colspan="2">Date</td>
                            <td colspan="2">Hotline Keeper</td>
                            <td>Total of Calls</td>
                            <td>Time</td>
                            <td>Phone No.</td>
                            <td>Content</td>
                            <td>Handled By</td>
                            <td>Issue</td>
                        </tr>';

        foreach ($items as $item)
        {
            $content .= getLastNotedIssuesContent($item);
        }

        $content .= '</table><br/>';
    }
    echo $content;
}

function getLastNotedIssuesContent($bug)
{
    global $LINK, $STATUS_COLOR_LIST;

    $result = '';
    $row = 0;
    foreach ($bug as $item)
    {
        $result .= "<tr style=\"background: #{$STATUS_COLOR_LIST[$item['status']]}\">
                        <td>{$item['day_of_month']}</td>
                        <td>{$item['day_of_week']}</td>
                        <td>{$item['time_range']}</td>
                        <td>{$item['hotline_keeper']}</td>
                        <td></td>
                        <td>{$item['time']}</td>
                        <td>{$item['tel_no']}</td>
                        <td>{$item['content']}</td>
                        <td>{$item['handled_by']}</td>
                        <td><a href=\"{$LINK}{$item['bug_id']}\">Issue {$item['bug_id']}</a></td>
                    </tr>";
        $row++;
    }
    return $result;
}
