<?php

class UnderwritingPlugin extends MantisPlugin
{
    const PREFIX = 'UW -';

    private $noteRequiredStatuses = [
        DECLINED,
        INFOREQUEST,
        INFOSUBMITTED,
        OFFERED,
        REUNDERWRITE,
        WITHDRAWN
    ];

    function register()
    {
        $this->name        = 'Underwriting';
        $this->description = 'Adds Underwriting functions.';
        $this->version     = '1.0.0';
        $this->author      = 'Nghiem Le';
        $this->contact     = 'nghiemle@pacificcross.com.vn';
        $this->url         = 'http://pacificcross.com.vn';
        $this->requires['MantisCore'] = '2.0';
    }

    function init()
    {
        plugin_event_hook( 'EVENT_LAYOUT_RESOURCES', 'onAddScript' );

        plugin_event_hook( 'EVENT_VIEW_BUG_EXTRA', 'onAddViewHiddenFields' );
        plugin_event_hook( 'EVENT_REPORT_BUG_FORM', 'onAddReportHiddenFields' );
        plugin_event_hook( 'EVENT_UPDATE_BUG_FORM', 'onAddUpdateHiddenFields' );

        plugin_event_hook( 'EVENT_REPORT_BUG_DATA', 'onPreReportIssue' );
        plugin_event_hook( 'EVENT_UPDATE_BUG_DATA', 'onPreUpdateIssue' );

        plugin_event_hook( 'EVENT_REPORT_BUG', 'onPostReportIssue' );
        plugin_event_hook( 'EVENT_UPDATE_BUG', 'onPostUpdateIssue' );
    }

    function onAddScript( $p_event )
    {
        echo '<script type="text/javascript" src="' . plugin_file( 'underwriting-v2.js' ) . '"></script>';
    }

    function onAddViewHiddenFields( $p_event, $p_bug_id )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        $t_category_id = bug_get_field( $p_bug_id, 'category_id' );

        $this->addProjectIdField( $t_project_id );
        echo '<tr style="display: none"><td><input type="hidden" id="category_id" value="' . $t_category_id . '" /></td></tr>';
        echo '<tr style="display: none"><td><input type="hidden" id="show_duplicated" value="yes" /></td></tr>';
    }

    function onAddReportHiddenFields( $p_event, $p_project_id )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        $this->addProjectIdField( $p_project_id );
    }

    function onAddUpdateHiddenFields( $p_event, $p_bug_id )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        $t_user_id = auth_get_current_user_id();
        $t_user_access_level = user_get_access_level( $t_user_id, $t_project_id );

        $t_is_accessible = access_compare_level(
            $t_user_access_level,
            config_get( 'show_duplicated_on_edit', null, null, $t_project_id )
        );
        if( $t_is_accessible ) {
            echo '<input type="hidden" id="show_duplicated" value="yes" />';
        }

        $this->addProjectIdField( $t_project_id );
    }

    function addProjectIdField( $p_project_id )
    {
        echo '<tr style="display: none"><td>';
        echo '<input type="hidden" id="project_underwritings" value="1" />';
        echo '</td></tr>';
    }

    function onPreReportIssue( $p_event, $p_bug )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return $p_bug;
        }

        return $this->autoSummary( $p_bug );
    }

    function onPreUpdateIssue( $p_event, $p_updated_bug, $p_existing_bug )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return $p_updated_bug;
        }

        $t_bugnote_text = gpc_get_string( 'bugnote_text', '' );
        if(
            in_array( $p_updated_bug->status, $this->noteRequiredStatuses ) &&
            strlen( $t_bugnote_text ) == 0 &&
            strpos(project_get_name( $p_existing_bug->project_id ), self::PREFIX) !== false
        ) {
            trigger_error( ERROR_BUGNOTE_NOT_FOUND, ERROR );
        }

        return $this->autoSummary( $p_updated_bug );
    }

    function autoSummary( $p_issue )
    {
        if( strpos( $p_issue->summary, '[automatic]' ) === false ) {
            return $p_issue;
        }
        
        $t_mem_count = gpc_get_string( 'custom_field_' . MEM_COUNT, '' );
        if( $t_mem_count == '' ) {
            $t_mem_count = 'N/A';
        }

        $t_poho_name = gpc_get_string( 'custom_field_' . POHO_NAME, '' );
        if( $t_poho_name == '' ) {
            $t_poho_name = 'N/A';
        }

        $t_pocy_no = gpc_get_string( 'custom_field_' . POCY_NO, '' );
        if( $t_pocy_no == '' ) {
            $t_pocy_no = 'N/A';
        }

        $t_mbr_name = gpc_get_string( 'custom_field_' . MBR_NAME, '' );
        if( $t_mbr_name == '' ) {
            $t_mbr_name = 'N/A';
        }

        $t_mbr_no = gpc_get_string( 'custom_field_' . MBR_NO, '' );
        if( $t_mbr_no == '' ) {
            $t_mbr_no = 'N/A';
        }

        if( $p_issue->category_id == CAT_APPLICATION ) {
            $p_issue->summary = "{$t_poho_name} - {$t_pocy_no} - {$t_mem_count} members - {$t_mbr_name} - {$t_mbr_no}";
        } elseif( $p_issue->category_id == CAT_POLICY ) {
            $p_issue->summary = "{$t_poho_name} - {$t_pocy_no} - {$t_mem_count} members";
        }
        return $p_issue;
    }

    function onPostReportIssue( $p_event, $p_issue, $p_issue_id )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        if( $p_issue->category_id == CAT_APPLICATION ) {
            $this->updateDuplicatedValue( $p_issue_id );
        }
    }

    function onPostUpdateIssue( $p_event, $p_existing_bug, $p_updated_bug )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        $t_user_id = auth_get_current_user_id();
        $t_user_access_level = user_get_access_level( $t_user_id, $t_project_id );
        $t_show_duplicalted = access_compare_level(
            $t_user_access_level,
            config_get( 'show_duplicated_on_edit', null, null, $t_project_id )
        );
        if(
            !$t_show_duplicalted &&
            $t_updated_bug->category_id == CAT_APPLICATION
        ) {
            $this->updateDuplicatedValue( $p_updated_bug->id );
        }
    }

    function updateDuplicatedValue( $p_issue_id )
    {
        $t_member = $this->getMemberNameAndDob( $p_issue_id );

        $t_new_duplicated = $this->checkDuplicated(
            $t_member['name'], $t_member['dob'], $p_issue_id
        );
        custom_field_set_value(
            DUPLICATED, $p_issue_id,
            $t_new_duplicated ? 'Yes' : ''
        );

        $t_new_duplicated_with_crm = $this->checkDuplicatedWithCRM(
            $t_member['name'], $t_member['dob'], $t_member['reporter']
        );
        custom_field_set_value(
            DUPLICATED_WITH_CRM, $p_issue_id,
            $t_new_duplicated_with_crm ? 'Yes' : ''
        );

        $t_summary = $t_member['summary'];
        if(
            ( $t_new_duplicated || $t_new_duplicated_with_crm ) &&
            strpos( $t_summary, 'DUPLICATED' ) === false
        ) {
            $t_summary .= ' DUPLICATED';
        }
        elseif(
            !$t_new_duplicated && $t_new_duplicated_with_crm &&
            strpos( $t_summary, 'DUPLICATED' ) !== false
        ) {
            $t_summary .= str_replace(' DUPLICATED', '', $t_summary);
        }
        bug_set_field( $p_issue_id, 'summary', $t_summary );
    }

    function getMemberNameAndDob( $p_bug_id )
    {
        $t_query = 'SELECT
                        user.email reporter,
                        bug.summary,
                        REPLACE(
                            REPLACE(
                                LOWER(name.value),
                                \' \', \'\'
                            ),
                            \'đ\', \'d\'
                        ) name,
                        dob.value dob,
                        duplicated.value duplicated,
                        duplicated_with_crm.value duplicated_with_crm
                    FROM {bug} bug
                        INNER JOIN {user} user
                                ON bug.reporter_id = user.id
                        INNER JOIN {custom_field_string} name
                                ON name.bug_id = bug.id
                               AND name.field_id = ' . MBR_NAME . '
                        INNER JOIN {custom_field_string} dob
                                ON dob.bug_id = bug.id
                               AND dob.field_id = ' . DOB . '
                         LEFT JOIN {custom_field_string} duplicated
                                ON duplicated.bug_id = bug.id
                               AND duplicated.field_id = ' . DUPLICATED . '
                         LEFT JOIN {custom_field_string} duplicated_with_crm
                                ON duplicated_with_crm.bug_id = bug.id
                               AND duplicated_with_crm.field_id = ' . DUPLICATED_WITH_CRM . '
                    WHERE bug.id = ' . db_param();
        $t_result = db_query( $t_query, [ $p_bug_id ] );
        return db_fetch_array( $t_result );
    }

    function checkDuplicated( $p_member_name = false, $p_dob = false, $p_id = false )
    {
        if( $p_member_name === false || $p_dob === false ) {
            return false;
        }

        $t_query = 'SELECT COUNT(*)
                    FROM {custom_field_string} member
                        JOIN {custom_field_string} dob
                          ON member.bug_id = dob.bug_id
                         AND member.field_id = ' . MBR_NAME . '
                         AND dob.field_id = ' . DOB . '
                         AND REPLACE(
                                REPLACE(
                                    LOWER(member.value),
                                    \' \', \'\'
                                ),
                                \'đ\', \'d\'
                              ) LIKE ' . db_param() . '
                         AND dob.value = ' . db_param() . '
                         AND member.bug_id != ' . db_param() . '
                         AND dob.bug_id != ' . db_param();
        $t_result = db_query( $t_query, [ $p_member_name, $p_dob, $p_id, $p_id ] );
        $t_count = db_result( $t_result );

        return $t_count > 0 ? true : false;
    }

    function checkDuplicatedWithCrm( $p_member_name = false, $p_dob = false, $p_reporter = false )
    {
        if( $p_member_name === false || $p_dob === false || $p_reporter === false ) {
            return false;
        }

        $t_crm_db = new PDO(
            'mysql:host=' . CRM_DB_HOST
               . ';port=' . CRM_DB_PORT
               . ';dbname=' . CRM_DB_NAME
               . ';charset=utf8',
            CRM_DB_USER,
            CRM_DB_PASS
        );
        $t_sql = "SELECT COUNT(*) dup_count
                  FROM vtiger_contactdetails details
                    JOIN vtiger_contactsubdetails subdetails
                      ON details.contactid = subdetails.contactsubscriptionid
                     AND details.insured_type = 'Healthcare'
                     AND subdetails.birthday is not null
                    JOIN vtiger_crmentity entity
                      ON details.contactid = entity.crmid
                     AND entity.setype = 'Contacts'
                     AND entity.deleted = 0
                    JOIN vtiger_users user
                      ON user.id = entity.smownerid
                  WHERE ? LIKE CONCAT('%', REPLACE(REPLACE(LOWER(details.firstname), 'đ', 'd'), ' ', ''), '%')
                    AND ? LIKE CONCAT('%', REPLACE(REPLACE(LOWER(details.lastname), 'đ', 'd'), ' ', ''), '%')
                    AND ? = YEAR(subdetails.birthday)
                    AND ? != user.email1";
        $t_params = [
            $p_member_name, $p_member_name,
            date('Y', $p_dob),
            $p_reporter
        ];
        $t_result = $this->query($t_crm_db, $t_sql, $t_params, true);
        if( !$t_result || $t_result == null ) {
            return false;
        } else {
            return $t_result[0]['dup_count'] > 0;
        }
    }

    function query($p_pdo, $p_sql, $p_params = [], $p_return = true)
    {
        $t_stmt = $p_pdo->prepare( $p_sql );
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
}
