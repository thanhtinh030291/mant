<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Set an existing file private or public.
 *
 * @package MantisBT
 * @copyright Copyright 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses core.php
 * @uses access_api.php
 * @uses authentication_api.php
 * @uses bug_api.php
 * @uses file_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses error_api.php
 * @uses form_api.php
 * @uses gpc_api.php
 * @uses helper_api.php
 * @uses print_api.php
 * @uses string_api.php
 */

require_once( 'core.php' );
require_api( 'access_api.php' );
require_api( 'authentication_api.php' );
require_api( 'bug_api.php' );
require_api( 'file_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'error_api.php' );
require_api( 'form_api.php' );
require_api( 'gpc_api.php' );
require_api( 'helper_api.php' );
require_api( 'print_api.php' );
require_api( 'string_api.php' );

form_security_validate( 'bug_file_set_view_state' );

$f_file_id	= gpc_get_int( 'file_id' );
$f_file_ids	= gpc_get_string( 'file_ids' );
$f_file_ids	= explode(',', $f_file_ids);
$f_private	= gpc_get_bool( 'private' );

$t_bug_id = file_get_field( $f_file_id, 'bug_id' );

$t_bug = bug_get( $t_bug_id, true );
if( $t_bug->project_id != helper_get_current_project() ) {
	# in case the current project is not the same project of the bug we are viewing...
	# ... override the current project. This to avoid problems with categories and handlers lists etc.
	$g_project_override = $t_bug->project_id;
}

# Check if the bug is readonly
if( bug_is_readonly( $t_bug_id ) ) {
	error_parameters( $t_bug_id );
	trigger_error( ERROR_BUG_READ_ONLY_ACTION_DENIED, ERROR );
}

# Check if the current user is allowed to change the view state of this file
$t_user_id = file_get_field( $f_file_id, 'user_id' );
if( $t_user_id == auth_get_current_user_id() ) {
	access_ensure_file_level( config_get( 'bugnote_user_change_view_state_threshold' ), $f_file_id );
} else {
	access_ensure_file_level( config_get( 'update_file_threshold' ), $f_file_id );
	access_ensure_file_level( config_get( 'change_view_status_threshold' ), $f_file_id );
}

file_set_view_state( $f_file_id, $f_private );
if( !empty( $f_file_ids ) ) {
    foreach( $f_file_ids as $file_id ) {
        file_set_view_state( $file_id, $f_private );
    }
}

form_security_purge( 'bug_file_set_view_state' );

print_successful_redirect( string_get_bug_view_url( $t_bug_id ) . '#files' );
