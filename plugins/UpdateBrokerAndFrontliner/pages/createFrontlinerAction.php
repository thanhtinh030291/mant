<?php


$f_name		= gpc_get_string( 'name' );
$f_code		= gpc_get_string( 'code' );
$f_user_id	= gpc_get_string( 'user_id', '');
$f_project_id	= gpc_get_string( 'project_id', '');

$t_query = 'SELECT *
        FROM broker_project
        WHERE user_id =' .db_param() .' and project_id = '.db_param();
$t_result = db_query( $t_query,[$f_user_id , $f_project_id] );
$data_broker_project = db_fetch_array( $t_result );

if($data_broker_project == false){
	$t_query = 'INSERT INTO broker_project (user_id, project_id)
        VALUES ('.db_param().','.db_param().')';
	$t_result = db_query( $t_query,[$f_user_id , $f_project_id] );

	$t_query = 'SELECT *
        FROM broker_project
        WHERE user_id =' .db_param() .' and project_id = '.db_param();
		$t_result = db_query( $t_query,[$f_user_id , $f_project_id] );
	$data_broker_project = db_fetch_array( $t_result );
	
}

	# Execute a 2nd query while the 1st is still being built
$t_query = 'INSERT INTO frontliners
			(code, name, broker_project_id)
			VALUES ('.db_param().','.db_param().','.db_param().')';
$t_query_params = array( $f_code, $f_name, $data_broker_project['id']);
$t_result = db_query( $t_query, $t_query_params );

$t_redirect_url = '/plugin.php?page=UpdateBrokerAndFrontliner/indexFrontliner';
layout_page_header( null, $t_result ? $t_redirect_url : null );
layout_page_begin( 'manage_overview_page.php' );
html_operation_successful( $t_redirect_url );
layout_page_end();

