<?php

error_reporting(E_ERROR);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/config_inc.php');
require_once(__DIR__ . '/../library/taq/pdooci/src/PDO.php');

define('USERNAME', 'administrator');
define('PASSWORD', '@dmin##VDP');

define('BRKR_ID', 60);
define('FRLN_ID', 85);
define('EFF_DATE_ID', 80);
define('PROJECT_ID', 8);
define('REPORTER_ID', 115);
define('VIEW_STATE_ID', 10);
define('RENEW_ID', 61);
define('NEXT_PAYMENT_ID', 62);
define('POL_NO_ID', 78);
define('POHO_NAME_ID', 55);
define('MEMB_COUNT_ID', 74);
define('MBR_NAME_ID', 11);
define('DOB_ID', 56);
define('ID_PASSPORT_ID', 57);
define('SRC_ID', 59);
define('FIN_PLAN_ID', 62);
define('ORIGINAL_APP_ID', 75);
define('POLICY_PACKAGE_ID', 76);
define('PAYMENT_MODE_ID', 82);
define('POLICY_YEAR_ID', 83);
define('RN_NP_STATUS_ID', 84);

define('CUSTOM_TYPE_STRING', 0);
define('CUSTOM_TYPE_ENUM', 3);

try
{
    echo date('Y-m-d H:i:s') . "\n";

    $url = str_replace(
        '/data/www/pcv_etalk_pacificcross_com_vn',
        'https://pcv-etalk.pacificcross.com.vn',
        __DIR__
    );
    $url .= "/soap/mantisconnect.php?wsdl";
    $api = new SoapClient($url, [
        'stream_context' => stream_context_create([
            'http' => [
                'user_agent' => 'PHPSoapClient'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]),
        'cache_wsdl' => WSDL_CACHE_NONE,
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1
    ]);
    $api->soap_defencoding = 'UTF-8';
    $api->decode_utf8 = false;

    $hbs = new PDOOCI\PDO(
        "//{$g_hbs_hostname}:{$g_hbs_port}/{$g_hbs_database_name};charset=utf8",
        $g_hbs_username,
        $g_hbs_password
    );
    $mts = new PDO(
        "mysql:host={$g_hostname};port=3306;dbname={$g_database_name};charset=utf8",
        $g_db_username,
        $g_db_password
    );
}
catch (Exception $e)
{
    echo $e->getMessage();
    exit;
}

try
{
    $policies = getProformaPolicies($hbs, $mts);

    updateBrokerOptions($mts, CUSTOM_TYPE_STRING);
    updateFrontlinerOptions($mts, CUSTOM_TYPE_STRING);

    foreach ($policies as $policy)
    {
        createPolicyAsIssue($api, $policy);
    }
}
catch (Exception $e)
{
    echo $e->getMessage();

    updateBrokerOptions($mts, CUSTOM_TYPE_ENUM);
    updateFrontlinerOptions($mts, CUSTOM_TYPE_ENUM);
}

updateBrokerOptions($mts, CUSTOM_TYPE_ENUM);
updateFrontlinerOptions($mts, CUSTOM_TYPE_ENUM);

function getProformaPolicies($hbs, $mts)
{
    $policies = [];
    $data = $hbs->query(file_get_contents(
        __DIR__ . DIRECTORY_SEPARATOR . 'hbs_proforma.sql'
    ));
    foreach ($data->fetchAll(PDO::FETCH_ASSOC) as $item)
    {
        $ids = getRelatedMemberIds($mts, $item['pol_no']);
        list($project, $reporter) = getProjectAndReporter($mts, $item['frln'], $item['brkr']);
        $policies[] = (object) [
            'membIds' => $ids,
            'broker' => $item['brkr'],
            'frontliner' => $item['frln'],
            'summary' => "{$item['poho_name']} - {$item['pol_no']} - {$item['memb_count']} members",
            'description' => "{$item['poho_name']} - {$item['pol_no']} - {$item['memb_count']} members",
            'category' => 'Policy',
            'sticky' => false,
            'project' => $project,
            'reporter' => $reporter,
            'view_state' => (object) [
                'id' => VIEW_STATE_ID,
                'name' => 'public'
            ],
            'priority' => (object) [
                'name' => 'normal'
            ],
            'severity' => (object) [
                'name' => 'text'
            ],
            'reproducibility' => (object) [
                'name' => 'always'
            ],
            'status' => (object) [
                'id' => ($item['rn_np_status'] === 'Renew' ? RENEW_ID : NEXT_PAYMENT_ID),
                'name' => ($item['rn_np_status'] === 'Renew' ? 'renew' : 'next_payment')
            ],
            'custom_fields' => [
                (object) [
                    'field' => (object) [
                        'id' => EFF_DATE_ID,
                        'name' => 'Policy Effective Date'
                    ],
                    'value' => strtotime(trim($item['pol_eff_date']))
                ],
                (object) [
                    'field' => (object) [
                        'id' => POL_NO_ID,
                        'name' => 'Policy No.'
                    ],
                    'value' => trim($item['pol_no'])
                ],
                (object) [
                    'field' => (object) [
                        'id' => POHO_NAME_ID,
                        'name' => 'Policy Holder Name'
                    ],
                    'value' => trim($item['poho_name'])
                ],
                (object) [
                    'field' => (object) [
                        'id' => MEMB_COUNT_ID,
                        'name' => 'Number of Members'
                    ],
                    'value' => $item['memb_count']
                ],
                (object) [
                    'field' => (object) [
                        'id' => MBR_NAME_ID,
                        'name' => 'Member Name'
                    ],
                    'value' => 'N/A'
                ],
                (object) [
                    'field' => (object) [
                        'id' => DOB_ID,
                        'name' => 'Date Of Birth'
                    ],
                    'value' => 0
                ],
                (object) [
                    'field' => (object) [
                        'id' => ID_PASSPORT_ID,
                        'name' => 'ID/Passport'
                    ],
                    'value' => 'N/A'
                ],
                (object) [
                    'field' => (object) [
                        'id' => SRC_ID,
                        'name' => 'Source'
                    ],
                    'value' => 'Recruited'
                ],
                (object) [
                    'field' => (object) [
                        'id' => BRKR_ID,
                        'name' => 'Broker'
                    ],
                    'value' => trim($item['brkr'])
                ],
                (object) [
                    'field' => (object) [
                        'id' => FRLN_ID,
                        'name' => 'Frontliner'
                    ],
                    'value' => trim($item['frln'])
                ],
                (object) [
                    'field' => (object) [
                        'id' => FIN_PLAN_ID,
                        'name' => 'Final Plan'
                    ],
                    'value' => ''
                ],
                (object) [
                    'field' => (object) [
                        'id' => ORIGINAL_APP_ID,
                        'name' => 'Original App'
                    ],
                    'value' => 'Yes'
                ],
                (object) [
                    'field' => (object) [
                        'id' => POLICY_PACKAGE_ID,
                        'name' => 'Policy Package'
                    ],
                    'value' => 'Yes'
                ],
                (object) [
                    'field' => (object) [
                        'id' => PAYMENT_MODE_ID,
                        'name' => 'Payment Mode'
                    ],
                    'value' => trim($item['pay_mode'])
                ],
                (object) [
                    'field' => (object) [
                        'id' => POLICY_YEAR_ID,
                        'name' => 'Policy Year'
                    ],
                    'value' => trim($item['pol_year'])
                ],
                (object) [
                    'field' => (object) [
                        'id' => RN_NP_STATUS_ID,
                        'name' => 'RN/NP Status'
                    ],
                    'value' => trim($item['rn_np_status'])
                ]
            ]
        ];
    }
    return $policies;
}

function getRelatedMemberIds($pdo, $polNo)
{
    $customFields = $pdo->query("
        SELECT DISTINCT bug_id
        FROM mantis_custom_field_string_table
        WHERE field_id = " . POL_NO_ID . "
		  AND value = '{$polNo}'
    ");
    $ids = [];
    foreach ($customFields->fetchAll(PDO::FETCH_ASSOC) as $customField)
    {
        $ids[] = $customField['bug_id'];
    }
    return $ids;
}

function getProjectAndReporter($pdo, $frln, $brkr)
{
    $frontliners = $pdo->query("
        SELECT
            user.id user_id,
            user.username,
            proj.id proj_id,
            proj.name proj_name
        FROM frontliners frln
            JOIN broker_project brpr
              ON frln.broker_project_id = brpr.id
            JOIN mantis_project_table proj
              ON brpr.project_id = proj.id
            JOIN mantis_user_table user
              ON brpr.user_id = user.id
        WHERE CONCAT(frln.code, ' - ', frln.name) = '{$frln}'
        LIMIT 1
    ");
    foreach ($frontliners->fetchAll(PDO::FETCH_ASSOC) as $frontliner)
    {
        $project = (object) [
            'id' => $frontliner['proj_id'],
            'name' => $frontliner['proj_name']
        ];
        $reporter = (object) [
            'id' => $frontliner['user_id'],
            'name' => $frontliner['username']
        ];
        return [$project, $reporter];
    }

    $brokers = $pdo->query("
        SELECT
            user.id user_id,
            user.username,
            proj.id proj_id,
            proj.name proj_name
        FROM brokers brkr
            JOIN broker_project brpr
              ON brkr.broker_project_id = brpr.id
            JOIN mantis_project_table proj
              ON brpr.project_id = proj.id
            JOIN mantis_user_table user
              ON brpr.user_id = user.id
        WHERE CONCAT(brkr.code, ' - ', brkr.name) = '{$brkr}'
        LIMIT 1
    ");
    foreach ($brokers->fetchAll(PDO::FETCH_ASSOC) as $broker)
    {
        $project = (object) [
            'id' => $broker['proj_id'],
            'name' => $broker['proj_name']
        ];
        $reporter = (object) [
            'id' => $broker['user_id'],
            'name' => $broker['username']
        ];
        return [$project, $reporter];
    }

    $project = (object) [
        'id' => PROJECT_ID,
        'name' => 'Underwriting'
    ];
    $reporter = (object) [
        'id' => REPORTER_ID,
        'name' => 'PCV'
    ];

    return [$project, $reporter];
}

function updateBrokerOptions($pdo, $type)
{
    $stmt = $pdo->prepare("UPDATE mantis_custom_field_table SET type = ? WHERE id = " . BRKR_ID);
    $stmt->execute([$type]);
}

function updateFrontlinerOptions($pdo, $type)
{
    $stmt = $pdo->prepare("UPDATE mantis_custom_field_table SET type = ? WHERE id = " . FRLN_ID);
    $stmt->execute([$type]);
}

function createPolicyAsIssue($api, $policy)
{
    try
    {
        echo "Process {$policy->summary}\n";
        $relatedIds = $policy->membIds;
        unset($policy->membIds);
        unset($policy->broker);
        unset($policy->frontliner);

        $id = $api->mc_issue_add(USERNAME, PASSWORD, $policy);
        foreach ($relatedIds as $relatedId)
        {
            $api->mc_issue_relationship_add(
                USERNAME,
                PASSWORD,
                $relatedId,
                (object) [
                    'target_id' => $id,
                    'type' => (object) [
                        'id' => 1,
                        'name' => 'related to'
                    ]
                ]
            );
        }
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
        exit;
    }
}
