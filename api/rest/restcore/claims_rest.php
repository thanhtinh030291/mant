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
 * A webservice interface to Mantis Bug Tracker
 *
 * @package MantisBT
 * @copyright Copyright MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 */
 
use Mantis\Exceptions\ClientException;

$g_app->group('/claims', function() use ($g_app)
{
    # Find ID
    $g_app->get('/common/{common_id}', 'rest_claim_find');
    $g_app->get('/common/{common_id}/', 'rest_claim_find');
});

/**
 * Find bug_id from Common ID
 *
 * @param \Slim\Http\Request $p_request   The request.
 * @param \Slim\Http\Response $p_response The response.
 * @param array $p_args Arguments
 * @return \Slim\Http\Response The augmented response.
 */
function rest_claim_find(\Slim\Http\Request $p_request, \Slim\Http\Response $p_response, array $p_args)
{
    $t_issue_id = get_issue_id_from_custom_field(COMMON_ID, $p_args['common_id']);
    return $p_response->withJson(array('bug_id' => $t_issue_id));
}

/**
 * Get the id of an issue via the issue's custom field id and custom field value.
 *
 * @param string $p_username  The name of the user trying to delete the issue.
 * @param string $p_password  The password of the user.
 * @param string $p_custom_id  The custom field id of the issue to retrieve.
 * @param string $p_custom_value  The value of the custom field id of the issue to retrieve.
 * @return integer  The id of the issue with the given summary, 0 if there is no such issue.
 */
function get_issue_id_from_custom_field($p_custom_id, $p_custom_value)
{
	$query = "SELECT bug_id
		FROM mantis_custom_field_string_table
		WHERE field_id = $p_custom_id AND value = '$p_custom_value'";
	$result = db_query($query);
    $n = db_num_rows($result);
	if ($n > 1 OR $n < 1)
    {
		return 0;
	}
    $row = db_fetch_array($result);
    $t_issue_id = (int) $row['bug_id'];
    return $t_issue_id;
}
