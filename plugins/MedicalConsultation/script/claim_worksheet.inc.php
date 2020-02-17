<?php

function getApi()
{
    $url = str_replace(
        ['/data/www/pcv_etalk_test_pacificcross_com_vn', 'plugins/MedicalConsultation/script'],
        ['https://pcv-etalk-test.pacificcross.com.vn', 'api/soap/mantisconnect.php?wsdl'],
        __DIR__
    );
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
    return $api;
}

function getNonConsultedClaimIssues($pdo)
{
    echo "Get Non Consulted Claims\n";
    $sql = file_get_contents('non_consulted_claims.sql');
    $sql = sprintf($sql, MBR_NO_FIELD, CL_NO_FIELD, COMMON_ID_FIELD, MC_ISSUE_PRJ_ID, MC_ISSUE_CAT_ID, CLAIMS_PRJS, FINISH_STATS);
    return query($pdo, $sql);
}

function addMedicalConsultationIssue($api, $issue)
{
    echo "Add Medical Consultation Issue for {$issue['id']}\n";
    $mcIssue = (object) [
        'summary' => "Medical Consultation for {$issue['summary']}",
        'description' => $issue['summary'],
        'category' => MC_ISSUE_CAT_NAME,
        'sticky' => false,
        'project' => (object) [
            'id' => MC_ISSUE_PRJ_ID,
            'name' => MC_ISSUE_PRJ_NAME
        ],
        'reporter' => (object) [
            'id' => USER_ID,
            'name' => USERNAME
        ],
        'view_state' => (object) [
            'id' => 10,
            'name' => 'public'
        ],
        'due_date' => strtotime(DUE_DATE),
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
            'id' => 10,
            'name' => 'new'
        ]
    ];
    $id = $api->mc_issue_add(USERNAME, PASSWORD, $mcIssue);

    $relationship = (object) [
        'target_id' => $issue['id'],
        'type' => (object) [
            'id' => 1,
            'name' => 'related to'
        ]
    ];
    $api->mc_issue_relationship_add(USERNAME, PASSWORD, $id, $relationship);

    addNote($api, $issue['id'], "A Medical Consultation Issue has been added for this Issue at #{$id}");

    return $id;
}

function addNote($api, $id, $note)
{
    echo "Add note for Issue {$id}\n";
    $note = (object) [
        'reporter' => (object) [
            'id' => USER_ID,
            'name' => USERNAME
        ],
        'text' => $note,
        'view_state' => (object) [
            'id' => 50,
            'name' => 'private'
        ]
    ];
    $api->mc_issue_note_add(USERNAME, PASSWORD, $id, $note);
}

function addMedicalRelationship($pdo, $bugId, $claimBugId, $clNo, $commonId)
{
    echo "Add Medical Relationship for {$bugId}\n\n";
    $sql = 'INSERT INTO claim_worksheet(bug_id, claim_bug_id, cl_no, common_id) VALUES (?, ?, ?, ?)';
    return query($pdo, $sql, [$bugId, $claimBugId, $clNo, $commonId], false);
}

function getClaim($pdo, $issue)
{
    echo "Get Claim Worksheet for Claim Issue {$issue['id']}\n";

    $clNo = preg_replace('/[^0-9]/', '', $issue['cl_no']);
    $commonId = preg_replace('/[^0-9]/', '', $issue['common_id']);
    $lines = getCurrentClaim($pdo, $clNo, $commonId);
    if (!$lines)
    {
        return false;
    }
    return true;
}

function getCurrentClaim($pdo, $clNo, $commonId)
{
    echo "Get Current Claim for Claim No {$clNo} and Common ID {$commonId}\n";
    $sql = file_get_contents('hbs_current_claims.sql');
    return query($pdo, $sql, [$clNo, "%{$commonId}"]);
}

function query($pdo, $sql, $params = [], $return = true)
{
    $stmt = $pdo->prepare($sql);
    if (!$stmt)
    {
        return false;
    }

    $rows = [];
    if ($stmt->execute($params))
    {
        if (!$return)
        {
            return true;
        }

        while ($row = $stmt->fetch())
        {
            $rows[] = $row;
        }
    }
    return $rows;
}

