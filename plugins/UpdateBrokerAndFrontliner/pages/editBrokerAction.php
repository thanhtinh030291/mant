<?php

$f_id	= gpc_get_string( 'id' );
$f_name		= gpc_get_string( 'name' );
$f_code		= gpc_get_string( 'code' );
$f_broker_project_id	= gpc_get_string( 'broker_project_id', '');


$t_query = 'UPDATE brokers
			SET code=' . db_param() . ', name=' . db_param() . ',
				broker_project_id=' . db_param() . ' WHERE id=' . db_param();
	$t_query_params = array( $f_code, $f_name, $f_broker_project_id, $f_id );

$t_result = db_query( $t_query, $t_query_params );

$t_redirect_url = '/plugin.php?page=UpdateBrokerAndFrontliner/indexBroker';
layout_page_header( null, $t_result ? $t_redirect_url : null );
layout_page_begin( 'manage_overview_page.php' );
html_operation_successful( $t_redirect_url );
layout_page_end();

