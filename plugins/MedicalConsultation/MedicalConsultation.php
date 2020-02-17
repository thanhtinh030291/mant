<?php

require_once( 'script/constant.inc.php' );
require_once( dirname( dirname( __DIR__ ) ) . '/library/taq/pdooci/src/PDO.php' );

class MedicalConsultationPlugin extends MantisPlugin
{
    const PREFIX = 'MC - ';

    function register()
    {
        $this->name        = 'MedicalConsultation';
        $this->description = 'MedicalConsultation';
        $this->version     = '1.0';
        $this->author      = 'Nghiem Le';
        $this->contact     = 'nghiemle@pacificcross.com.vn';
        $this->url         = 'http://pacificcross.com.vn';
        $this->requires['MantisCore'] = '2.0';
    }

    function hooks()
    {
		return [
			'EVENT_VIEW_BUG_DETAILS' => 'onViewBugDetails',
			'EVENT_UPDATE_BUG' => 'onUpdateBug',
            'EVENT_BUGNOTE_ADD_FORM' => 'onAddHiddenField',
            'EVENT_BUGNOTE_ADD' => 'onAddBugNote',
            'EVENT_VIEW_BUGNOTE' => 'onViewBugNote'
		];
	}

    function onViewBugDetails( $p_event, $p_bug_id )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        $t_query = 'SELECT cl_no, claim_bug_id
                    FROM claim_worksheet
                    WHERE bug_id=' . db_param() . '
                    LIMIT 1';
        $t_params = array( $p_bug_id );
        $t_result = db_query( $t_query, $t_params );
        $t_row = db_fetch_array( $t_result );

        $t_worksheet = $this->getClaimWorksheet( $t_row['cl_no'], $t_row['common_id'] );

        $this->showMemberInfo( $t_worksheet[ 'member' ] );
        $this->showClaimLines( $t_worksheet[ 'lines' ] );
        $this->showHistory( $t_worksheet[ 'history' ] );
    }

    function getClaimWorksheet( $p_cl_no, $p_common_id )
    {
        $this->pdo = new PDOOCI\PDO(
            '//' . HBS_HOST . ':' . HBS_PORT . '/' . HBS_DBID . ';charset=utf8',
            HBS_USER, HBS_PASS
        );

        $t_lines = $this->getCurrentClaim( $p_cl_no, $p_common_id );
        if( !$t_lines ) {
            return false;
        }

        $t_member = $this->getMember( $t_lines[0]['memb_oid'], $t_lines[0]['popl_oid'] );
        if( !$t_member ) {
            return false;
        }

        $t_history = $this->getClaimHistory( $t_lines[0]['memb_oid'], $t_lines[0]['clam_oid'] );

        return [
            'member' => $t_member,
            'lines' => $t_lines,
            'history' => $t_history
        ];
    }

    function getCurrentClaim( $p_cl_no, $p_common_id )
    {
        $t_sql = file_get_contents( __DIR__ . '/script/hbs_current_claims.sql' );
        return $this->queryHbs( $t_sql, [ $p_cl_no, "%{$p_common_id}" ] );
    }

    function getMember( $p_memb_oid, $p_popl_oid )
    {
        $t_sql = file_get_contents( __DIR__ . '/script/hbs_claimed_members.sql' );
        return $this->queryHbs( $t_sql, [ $p_memb_oid, $p_popl_oid ] );
    }

    function getClaimHistory( $p_memb_oid, $p_clam_oid )
    {
        $t_sql = file_get_contents( __DIR__ . '/script/hbs_claim_history.sql' );
        return $this->queryHbs( $t_sql, [ $p_memb_oid, $p_clam_oid ] );
    }

    function queryHbs( $p_sql, $p_params = [], $p_return = true )
    {
        $t_stmt = $this->pdo->prepare( $p_sql );
        if( !$t_stmt ) {
            return false;
        }

        $t_rows = [];
        if( $t_stmt->execute( $p_params ) ) {
            if( !$p_return ) {
                return true;
            }

            while( $t_row = $t_stmt->fetch() ) {
                $t_rows[] = $t_row;
            }
        }
        return $t_rows;
    }

    function showMemberInfo( $p_members )
    {
        $t_member = $p_members[ 0 ];
        $t_event = '';
        foreach( $p_members as $p_member) {
            $t_event .= $p_member[ 'event' ] . "\n";
        }

        echo '<tr>';
        echo '<th class="category">Member Name</th><td>' . $t_member[ 'mbr_name' ] . '</td>';
        echo '<th class="category">Date of Birth</th><td>' . $t_member[ 'dob' ] . '</td>';
        echo '<th class="category">Gender</th><td>' . $t_member[ 'gender' ] . '</td>';
        echo '</tr><tr>';
        echo '<th class="category">Policy Holder</th><td>' . $t_member[ 'poho_name' ] . '</td>';
        echo '<th class="category">Policy No</th><td>' . $t_member[ 'pocy_no' ] . '</td>';
        echo '<th class="category">Policy Eff Date</th><td>' . $t_member[ 'pocy_eff_date' ] . '</td>';
        echo '</tr><tr>';
        echo '<th class="category">Plan</th><td>' . $t_member[ 'pocy_plan_desc' ] . '</td>';
        /*
        echo '<th class="category">Broker</th><td>' . $t_member[ 'broker' ] . '</td>';
        echo '<th class="category">Front Liner</th><td>' . $t_member[ 'frontliner' ] . '</td>';
        echo '</tr><tr>';
        */
        echo '<th class="category">Member No</th><td>' . $t_member[ 'mbr_no' ] . '</td>';
        echo '<th class="category">Member Eff Date</th><td>' . $t_member[ 'memb_eff_date' ] . '</td>';

        echo '</tr><tr class="spacer"><td colspan="6"></td></tr>';

        if( trim( $t_event ) === '' ) {
            echo '<tr><th class="category">Member Event</th><td colspan="5">No event</td></tr>';
            return;
        }

        $i = 2;
        echo '<tr><th class="category" colspan="6">Member Event</th></tr>';
        foreach( $p_members as $p_member) {
            $i = $i == 1 ? 2 : 1;
            echo '<tr><td colspan="6">' . $p_member[ 'event' ] . '</td></tr>';
        }
    }

    function showClaimLines( $p_lines )
    {
        if( count( $p_lines ) === 0 ) {
            echo '<tr><th class="category">Claim Information</th><td colspan="5" class="status-10-color">No data</td></tr>';
            return;
        }

        echo '<tr class="spacer"><td colspan="6"></td></tr>';
        echo '<tr>
                  <th class="category">Claim Information</th>
                  <td colspan="5" class="status-10-color" style="color:black"><b>' . $p_lines[ 0 ][ 'cl_no' ] . '</b></td>
              </tr>';

        $i = 1;
        foreach( $p_lines as $t_line ) {
            $this->showClaimLine( $i++, $t_line );
        }
    }

    function showHistory( $p_history )
    {
        if( count( $p_history ) === 0 ) {
            echo '<tr class="spacer"><td colspan="6"></td></tr>';
            echo '<tr><th class="category">Claim History</th><td colspan="5">No data</td></tr>';
            return;
        }

        $t_claims = array();
        foreach( $p_history as $p_line ) {
            $t_claims[ $p_line[ 'cl_no' ] ] = isset( $t_claims[ $p_line[ 'cl_no' ] ] ) ? $t_claims[ $p_line[ 'cl_no' ] ] : [  ];
            $t_claims[ $p_line[ 'cl_no' ] ][  ] = $p_line;
        }

        foreach( $t_claims as $clNo => $t_claim ) {
            $i = 1;
            echo '<tr class="spacer"><td colspan="6"></td></tr>';
            echo '<tr><th class="category">Claim History</th><td colspan="5" class="status-15-color" style="color:black"><b>' . $clNo . '</b></td></tr>';
            foreach( $t_claim as $t_line ) {
                $this->showClaimLine( $i++, $t_line );
            }
        }
    }

    function showClaimLine( $p_index, $p_line )
    {
        $t_status = $p_line[ 'status' ] === 'RJ'
            ? '<b style="color: red">Rejected</b>'
            : (
                in_array($p_line[ 'status' ], array('PD', 'PV'))
                    ? '<b style="color: yellow">Pending</b>'
                    : (
                        $p_line[ 'status' ] === 'AC' && $p_line[ 'app_amt' ] == $p_line[ 'pres_amt' ]
                            ? '<b style="color: green">Accepted</b>'
                            : (
                                $p_line[ 'status' ] === 'AC' && $p_line[ 'app_amt' ] != $p_line[ 'pres_amt' ]
                                        ? '<b style="color: blue">Partially Accepted</b>' : ''
                            )
                    )
            );

        echo '</tr><tr>';
        echo '<th class="category">Claim Line #' . $p_index . '</th>';
        echo '<td colspan="5">' . $t_status . '</td>';
        echo '</tr><tr>';
        echo '<th class="category">Diagnosis Code</th><td>' . $p_line[ 'diag_code' ] . '</td>';
        echo '<th class="category">Diagnosis Description</th><td>' . $p_line[ 'diag_desc' ] . '</td>';
        echo '<th class="category">Provider</th><td>' . $p_line[ 'prov_name' ] . '</td>';
        echo '</tr><tr>';
        echo '<th class="category">Treatment</th><td>' . $p_line[ 'ben_type' ] . '</td>';
        echo '<th class="category">Presented Amount</th><td>' . number_format($p_line[ 'pres_amt' ]) . ' VND</td>';
        echo '<th class="category">Incur Date From</th><td>' . $p_line[ 'incur_date_from' ] . '</td>';
        echo '</tr><tr>';
        echo '<th class="category">Benefit</th><td>' . $p_line[ 'ben_head' ] . '</td>';
        echo '<th class="category">Approved Amount</th><td>' . number_format($p_line[ 'app_amt' ]) . ' VND</td>';
        echo '<th class="category">Incur Date To</th><td>' . $p_line[ 'incur_date_to' ] . '</td>';
        echo '</tr>';
    }

    function onUpdateBug( $p_event, $p_existing_bug, $p_updated_bug )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        if(
            $p_updated_bug->project_id == $p_existing_bug->project_id &&
            $p_updated_bug->project_id == MC_ISSUE_PRJ_ID &&
            $p_updated_bug->status != $p_existing_bug->status &&
            in_array($p_updated_bug->status, [MC_BEGIN_STATUS, MC_FINISH_STATUS])
        ) {
            $t_query = 'SELECT claim_bug_id, claim_note_id, time
                        FROM claim_worksheet
                        WHERE bug_id=' . db_param() . '
                        LIMIT 1';
            $t_params = array( $p_updated_bug->id );
            $t_result = db_query( $t_query, $t_params );
            $t_row = db_fetch_array( $t_result );

            if( $p_updated_bug->status == MC_BEGIN_STATUS ) {
                $t_now = date( 'Y-m-d H:i:s' );
                $t_text = "This Issue is begin consultation at #{$p_updated_bug->id} ({$t_now})";
                $t_id = bugnote_add( $t_row['claim_bug_id'], $t_text, '0:00', true );
                $t_query = 'UPDATE claim_worksheet
                            SET claim_note_id=' . db_param() . ',
                                time=' . db_param() . '
                            WHERE bug_id=' . db_param();
                $t_params = array( $t_id, $t_now, $p_updated_bug->id );
                $t_result = db_query( $t_query, $t_params );
            }
            elseif( $p_updated_bug->status == MC_FINISH_STATUS ) {
                $t_text = "This Issue is finish consultation at #{$p_updated_bug->id} ({$t_row['time']})";
                if( $t_row['claim_note_id'] == null ) {
                    bugnote_add( $t_row['claim_bug_id'], $t_text, '0:00', true );
                } else {
                    bugnote_set_text( $t_row['claim_note_id'], $t_text );
                }

                $t_query = 'UPDATE claim_worksheet
                            SET claim_note_id=NULL
                            WHERE bug_id=' . db_param();
                $t_params = array( $p_updated_bug->id );
                $t_result = db_query( $t_query, $t_params );
            }
        }

        if(
            $p_updated_bug->status == MC_RECONSULT_STATUS &&
            $p_updated_bug->status != $p_existing_bug->status
        ) {
            bug_set_field( $p_updated_bug->id, 'due_date', strtotime( DUE_DATE ) );
        } elseif( $p_updated_bug->status == MC_FINISH_STATUS ) {
            bug_set_field( $p_updated_bug->id, 'due_date', date_get_null() );
        }
    }

    function onAddHiddenField( $p_event, $p_bug_id )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        $t_query = 'SELECT project_id, status
                    FROM {bug}
                    WHERE id=' . db_param() . '
                    LIMIT 1';
        $t_params = array( $p_bug_id );
        $t_result = db_query( $t_query, $t_params );
        $t_row = db_fetch_array( $t_result );

        echo '<tr style="display: none"><td>';
        echo '<input type="hidden" name="view_time" value="' . date('Y-m-d H:i:s') . '" />';
        echo '</tr></td>';
    }

    function onAddBugNote( $p_event, $p_bug_id, $p_bugnote_id )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        $t_view_time = trim( gpc_get_string( 'view_time', null ) );

        db_param_push();
        $t_query = 'UPDATE {bugnote} N
                        JOIN {bugnote_text} T
                          ON N.bugnote_text_id = T.id
                    SET T.view_time=' . db_param() . '
                    WHERE N.id=' . db_param();
        $t_params = array( $t_view_time, $p_bugnote_id );
        db_query( $t_query, $t_params );
    }

    function onViewBugNote( $p_event, $p_bug_id, $p_activity_id, $p_private )
    {
        $t_query = 'SELECT B.project_id, T.view_time
                    FROM {bug} B
                        JOIN {bugnote} N
                          ON B.id = N.bug_id
                        JOIN {bugnote_text} T
                          ON N.bugnote_text_id = T.id
                    WHERE N.id=' . db_param() . '
                    LIMIT 1';
        $t_params = array( $p_activity_id );
        $t_result = db_query( $t_query, $t_params );
        $t_row = db_fetch_array( $t_result );
        $t_view_time = $t_row['view_time'];
        $t_project_id = $t_row['project_id'];

        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        if( !in_array($t_view_time, ['0000-00-00 00:00:00', null]) ) {
            echo '<tr class="bugnote visible-on-hover-toggle" id="vtime">
                    <td class="category">View Time</td>
                    <td class="bugnote-note bugnote-public">' . $t_view_time . '</td>
                 </tr>';
        }
    }
}
