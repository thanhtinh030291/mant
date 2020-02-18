<?php

$f_id	= gpc_get_string( 'id' );

$t_query = 'DELETE FROM frontliners
			WHERE id=' . db_param();
	$t_query_params = array( $f_id );

$t_result = db_query( $t_query, $t_query_params );

$t_redirect_url = '/plugin.php?page=UpdateBrokerAndFrontliner/indexFrontliner';
layout_page_header( null, $t_result ? $t_redirect_url : null );
layout_page_begin( 'manage_overview_page.php' );
html_operation_successful( $t_redirect_url );
layout_page_end();

