<?php

class CallLogPlugin extends MantisPlugin
{
    const PREFIX = 'CS -';
    const FORWARD = 5;
    const BACKWARD = 5;

    function register()
    {
        $this->name        = 'Call Log';
        $this->description = 'Adds an option for CS to choose between Call Log and Bug Note on adding bugnote.';
        $this->version     = '1.0.0';
        $this->author      = 'Nghiem Le';
        $this->contact     = 'nghiemle@pacificcross.com.vn';
        $this->url         = 'http://pacificcross.com.vn';
        $this->requires['MantisCore'] = '2.0';
    }

    function init()
    {
        plugin_event_hook( 'EVENT_LAYOUT_RESOURCES', 'onAddScript' );

        plugin_event_hook( 'EVENT_BUGNOTE_ADD_FORM', 'onAddOptionToAddForm' );
        plugin_event_hook( 'EVENT_BUGNOTE_EDIT_FORM', 'onAddOptionToEditForm' );

        plugin_event_hook( 'EVENT_BUGNOTE_ADD', 'onUpdateBugNote' );
        plugin_event_hook( 'EVENT_BUGNOTE_EDIT', 'onUpdateBugNote' );

        plugin_event_hook( 'EVENT_VIEW_BUGNOTE', 'onViewBugNote' );
    }

    function onAddScript( $p_event )
    {
        echo '<script type="text/javascript" src="' . plugin_file( 'call-log.js' ) . '"></script>';
    }

    function onAddOptionToAddForm( $p_event, $p_bug_id )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        $t_cur_year = intval( date( "Y" ) );
        $t_cur_month = intval( date( "m" ) );
        $t_cur_date = intval( date( "d" ) );
        $t_cur_hour = intval( date( "H" ) );
        $t_cur_minute = intval( date( "i" ) );

		echo '<tr>
				<th class="category" width="15%">Type</th>
				<td width="85%">
                    <select id="bugnote-type" name="bugnote_type" class="input-sm">
                        <option value="1">Bug Note</option>
                        <option value="2">Call Log</option>
                    </select>
				</td>
			  </tr>';

        echo '<tr id="call-log-time"><th class="category" width="15%">Call Time</th><td width="85%"><select name="call_log_date" class="input-sm" style="width: 60px">';
        for( $i = 1; $i <= 31; $i++ ) {
            $select = $i == $t_cur_date ? 'selected' : '';
            $day = sprintf( '%02d', $i );
            echo "<option value='{$day}' {$select}>{$day}</option>";
        }
        echo '</select>/<select name="call_log_month" class="input-sm" style="width: 60px">';
        for( $i = 1; $i <= 12; $i++ ) {
            $select = $i == $t_cur_month ? 'selected' : '';
            $month = sprintf( '%02d', $i );
            echo "<option value='{$month}' {$select}>{$month}</option>";
        }
        echo '</select>/<select name="call_log_year" class="input-sm" style="width: 60px">';
        for( $i = $t_cur_year - self::BACKWARD; $i <= $t_cur_year + self::FORWARD; $i++ ) {
            $select = $i == $t_cur_year ? 'selected' : '';
            $year = sprintf( '%04d', $i );
            echo "<option value='{$year}' {$select}>{$year}</option>";
        }
        echo '</select>&emsp;<select name="call_log_hour" class="input-sm" style="width: 60px">';
        for( $i = 0; $i < 24; $i++ ) {
            $select = $i == $t_cur_hour ? 'selected' : '';
            $hour = sprintf( '%02d', $i );
            echo "<option value='{$hour}' {$select}>{$hour}</option>";
        }
        echo '</select>:<select name="call_log_minute" class="input-sm" style="width: 60px">';
        for( $i = 0; $i < 60; $i++ ) {
            $select = $i == $t_cur_minute ? 'selected' : '';
            $minute = sprintf( '%02d', $i );
            echo "<option value='{$minute}' {$select}>{$minute}</option>";
        }
        echo '</select></td></tr>';

		echo '<tr id="call-log-tel-no">
				<th class="category" width="15%">Tel No</th>
				<td width="85%">
                    <select name="call_log_type" class="input-sm">
                        <option value="Deskphone">Deskphone</option>
                        <option value="Hotline">Hotline</option>
                    </select>
                    <input type="text" name="call_log_no"  size="80" class="input-sm" />
				</td>
			  </tr>';
    }

    function onAddOptionToEditForm( $p_event, $p_bug_id, $p_bugnote_id )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        $t_query = 'SELECT T.call_date, T.call_time, T.call_type, T.tel_no
                    FROM {bugnote} N
                        JOIN {bugnote_text} T
                          ON N.bugnote_text_id = T.id
                    WHERE N.id=' . db_param() . '
                    LIMIT 1';
        $t_params = array( $p_bugnote_id );
        $t_result = db_query( $t_query, $t_params );
        $t_row = db_fetch_array( $t_result );

        $t_call_date = isset( $t_row['call_date'] ) ? explode( '-', $t_row['call_date'] ) : null;
        $t_call_time = isset( $t_row['call_time'] ) ? explode( ':', $t_row['call_time'] ) : null;
        $t_call_type = $t_row['call_type'];
        $t_tel_no = $t_row['tel_no'];

        $t_cur_year = $t_call_date != null ? $t_call_date[0] : intval( date( "Y" ) );
        $t_cur_month = $t_call_date != null ? $t_call_date[1] : intval( date( "m" ) );
        $t_cur_date = $t_call_date != null ? $t_call_date[2] : intval( date( "d" ) );
        $t_cur_hour = $t_call_time != null ? $t_call_time[0] : intval( date( "H" ) );
        $t_cur_minute = $t_call_time != null ? $t_call_time[1] : intval( date( "i" ) );

        $t_call_log = $t_call_date != null;

		echo '<tr>
				<td width="85%">
                    <select id="bugnote-type" name="bugnote_type" class="input-sm">
                        <option value="1">Bug Note</option>
                        <option value="2" ' . ($t_call_log ? 'selected' : '') . '>Call Log</option>
                    </select>
				</td>
			  </tr>';

        echo '<tr id="call-log-time"><td width="85%"><select name="call_log_date" class="input-sm" style="width: 60px">';
        for( $i = 1; $i <= 31; $i++ ) {
            $select = $i == $t_cur_date ? 'selected' : '';
            $day = sprintf( '%02d', $i );
            echo "<option value='{$day}' {$select}>{$day}</option>";
        }
        echo '</select>/<select name="call_log_month" class="input-sm" style="width: 60px">';
        for( $i = 1; $i <= 12; $i++ ) {
            $select = $i == $t_cur_month ? 'selected' : '';
            $month = sprintf( '%02d', $i );
            echo "<option value='{$month}' {$select}>{$month}</option>";
        }
        echo '</select>/<select name="call_log_year" class="input-sm" style="width: 60px">';
        for( $i = $t_cur_year - self::BACKWARD; $i <= $t_cur_year + self::FORWARD; $i++ ) {
            $select = $i == $t_cur_year ? 'selected' : '';
            $year = sprintf( '%04d', $i );
            echo "<option value='{$year}' {$select}>{$year}</option>";
        }
        echo '</select>&emsp;<select name="call_log_hour" class="input-sm" style="width: 60px">';
        for( $i = 0; $i < 24; $i++ ) {
            $select = $i == $t_cur_hour ? 'selected' : '';
            $hour = sprintf( '%02d', $i );
            echo "<option value='{$hour}' {$select}>{$hour}</option>";
        }
        echo '</select>:<select name="call_log_minute" class="input-sm" style="width: 60px">';
        for( $i = 0; $i < 60; $i++ ) {
            $select = $i == $t_cur_minute ? 'selected' : '';
            $minute = sprintf( '%02d', $i );
            echo "<option value='{$minute}' {$select}>{$minute}</option>";
        }
        echo '</select></td></tr>';

		echo '<tr id="call-log-tel-no">
				<td width="85%">
                    <select name="call_log_type" class="input-sm">
                        <option value="Deskphone" ' . ( $t_call_type == 'Deskphone' ? 'selected' : '' ) . '>Deskphone</option>
                        <option value="Hotline" ' . ( $t_call_type == 'Hotline' ? 'selected' : '' ) . ' >Hotline</option>
                    </select>
                    <input type="text" name="call_log_no" value="' . $t_tel_no . '" size="80" class="input-sm" />
				</td>
			  </tr>';
    }

    function onUpdateBugNote( $p_event, $p_bug_id, $p_bugnote_id )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        db_param_push();

        $t_bugnote_type = trim( gpc_get_int( 'bugnote_type', 1 ) );
        if( $t_bugnote_type == 1 ) {
            $t_query = 'UPDATE {bugnote} N
                            JOIN {bugnote_text} T
                              ON N.bugnote_text_id = T.id
                        SET T.call_date=NULL,
                            T.call_time=NULL,
                            T.call_type=NULL,
                            T.tel_no=NULL
                        WHERE N.id=' . db_param();
            $t_params = [
                $p_bugnote_id
            ];

            db_query( $t_query, $t_params );
            return;
        }

        $t_year = sprintf( '%04d', trim( gpc_get_int( 'call_log_year', 0 ) ) );
        $t_month = sprintf( '%02d', trim( gpc_get_int( 'call_log_month', 0 ) ) );
        $t_date = sprintf( '%02d', trim( gpc_get_int( 'call_log_date', 0 ) ) );

        $t_hour = sprintf( '%02d', trim( gpc_get_int( 'call_log_hour', 0 ) ) );
        $t_minute = sprintf( '%02d', trim( gpc_get_int( 'call_log_minute', 0 ) ) );

        $t_type = trim( gpc_get_string( 'call_log_type', 'Deskphone' ) );
        $t_no = trim( gpc_get_string( 'call_log_no', '' ) );

        $t_query = 'UPDATE {bugnote} N
                        JOIN {bugnote_text} T
                          ON N.bugnote_text_id = T.id
                    SET T.call_date=' . db_param() . ',
                        T.call_time=' . db_param() . ',
                        T.call_type=' . db_param() . ',
                        T.tel_no=' . db_param() . '
                    WHERE N.id=' . db_param();
        $t_params = [
            "{$t_year}-{$t_month}-{$t_date}",
            "{$t_hour}:{$t_minute}",
            $t_type, $t_no,
            $p_bugnote_id
        ];

        db_query( $t_query, $t_params );
    }

    function onViewBugNote( $p_event, $p_bug_id, $p_activity_id, $p_private )
    {
        $t_project_id = helper_get_current_project();
        $t_project_name = project_get_name( $t_project_id );
        if( strpos( $t_project_name, self::PREFIX ) === false ) {
            return;
        }

        $t_query = 'SELECT
                        B.project_id,
                        N.id,
                        T.call_date,
                        T.call_time,
                        T.call_type,
                        T.tel_no
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
        $t_id = $t_row['id'];
        $t_call_date = $t_row['call_date'];
        $t_call_time = $t_row['call_time'];
        $t_call_type = $t_row['call_type'];
        $t_tel_no = $t_row['tel_no'];
        $t_project_id = $t_row['project_id'];

        if( $t_call_date != null ) {
            echo "<tr class='bugnote visible-on-hover-toggle' id='ctime{$t_id}'>
                    <td class='category'>Call Time</td>
                    <td class='bugnote-note bugnote-public'>{$t_call_date} {$t_call_time}</td>
                  </tr>
                  <tr class='bugnote visible-on-hover-toggle' id='ctel{$t_id}'>
                    <td class='category'>{$t_call_type}</td>
                    <td class='bugnote-note bugnote-public'>{$t_tel_no}</td>
                  </tr>";
        }
    }
}
