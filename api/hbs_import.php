<?php

// ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/config_inc.php');

//-----Start main-----
$members = getMembers($g_hostname, $g_database_name, $g_db_username, $g_db_password);
createApplication($members);
//-----End main-----


//-----Start functions-----
function getMembers($host, $dbname, $user, $pass) {
    $pdo = new PDO("mysql:host={$host};port=3306;dbname={$dbname};charset=utf8", $user, $pass);
    $membersInfo = $pdo->query("SELECT * FROM hbs_export");

    $members = [];
    foreach ($membersInfo as $memberInfo) {
        if (!isset($members[$memberInfo['mbr_no']])) {
            $members[$memberInfo['mbr_no']] = (object) [
                'summary' => "{$memberInfo['poho_name']} - {$memberInfo['pol_no']} - 0 members - {$memberInfo['mbr_name']} - {$memberInfo['mbr_no']}",
                'description' => "{$memberInfo['poho_name']} - {$memberInfo['pol_no']} - 0 members - {$memberInfo['mbr_name']} - {$memberInfo['mbr_no']}",
                'category' => 'Application',
                'sticky' => false,
                'project' => (object) [
                    'id' => 8,
                    'name' => 'Underwriting'
                ],
                'reporter' => (object) [
                    'id' => 115,
                    'name' => 'pcv'
                ],/*
                'handler' => (object) [
                    'id' => 115,
                    'name' => 'pcv'
                ],*/
                'view_state' => (object) [
                    'id' => 10,
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
                    'id' => 22,
                    'name' => 'finalized'
                ],
                'custom_fields' => [
                    (object) [
                        'field' => (object) [
                            'id' => 5,
                            'name' => 'Policy No'
                        ],
                        'value' => trim($memberInfo['pol_no'])
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 55,
                            'name' => 'Policy Holder Name'
                        ],
                        'value' => trim($memberInfo['poho_name'])
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 1,
                            'name' => 'Member No'
                        ],
                        'value' => trim($memberInfo['mbr_no'])
                     ],
                    (object) [
                        'field' => (object) [
                            'id' => 11,
                            'name' => 'Member Name'
                        ],
                        'value' => trim($memberInfo['mbr_name'])
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 56,
                            'name' => 'Date Of Birth'
                        ],
                        'value' => strtotime(trim($memberInfo['dob']))
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 57,
                            'name' => 'ID/Passport'
                        ],
                        'value' => (strlen($memberInfo['id_card']) > 0 ? trim($memberInfo['id_card']) : 'N/A')
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 59,
                            'name' => 'Source'
                        ],
                        'value' => 'Recruited'
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 60,
                            'name' => 'Broker'
                        ],
                        'value' => trim($memberInfo['brkr'])
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 8,
                            'name' => 'Paid Date'
                        ],
                        'value' => trim($memberInfo['paid_date'])
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 61,
                            'name' => 'Exclusion 1'
                        ],
                        'value' => trim($memberInfo['fin_offer'])
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 64,
                            'name' => 'Exclusion 2'
                        ],
                        'value' => ''
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 65,
                            'name' => 'Exclusion 3'
                        ],
                        'value' => ''
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 66,
                            'name' => 'Exclusion 4'
                        ],
                        'value' => ''
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 67,
                            'name' => 'Exclusion 5'
                        ],
                        'value' => ''
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 62,
                            'name' => 'Final Plan'
                        ],
                        'value' => preg_replace('/[[:^print:]]/', '', trim($memberInfo['fin_plan']))
                    ],
                    (object) [
                        'field' => (object) [
                            'id' => 74,
                            'name' => 'Number of Members'
                        ],
                        'value' => 0
                    ]
                ]
            ];
        } else {
            foreach ($members[$memberInfo['mbr_no']]->custom_fields as &$customField) {
                if ($customField->field->id === 61 && strlen($customField->value) == 0) {
                    $customField->value = "{$memberInfo['fin_offer']}";
                    break;
                }
                if ($customField->field->id === 64 && strlen($customField->value) == 0) {
                    $customField->value = "{$memberInfo['fin_offer']}";
                    break;
                }
                if ($customField->field->id === 65 && strlen($customField->value) == 0) {
                    $customField->value = "{$memberInfo['fin_offer']}";
                    break;
                }
                if ($customField->field->id === 66 && strlen($customField->value) == 0) {
                    $customField->value = "{$memberInfo['fin_offer']}";
                    break;
                }
                if ($customField->field->id === 67 && strlen($customField->value) == 0) {
                    $customField->value = "{$memberInfo['fin_offer']}";
                    break;
                }
            }
        }
    }
    return $members;
}

function createApplication($members) {
    foreach ($members as $member) {
        try {
            $url = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}" . str_replace('hbs_import.php', 'soap/mantisconnect.php?wsdl', $_SERVER['REQUEST_URI']);
            $soap = new SoapClient($url, [
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
            $soap->soap_defencoding = 'UTF-8';
            $soap->decode_utf8 = false;
            
            var_dump($soap->mc_issue_add('nghiemle', 'He!1@ngeI', $member));
        } catch (Exception $e) {
            echo $member->summary . "<br/>\n";
        }
    }
}
//-----End functions-----
