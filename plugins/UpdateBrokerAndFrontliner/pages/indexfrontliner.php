<?php
/**
 * MantisBT - A PHP based bugtracking system
 *
 * MantisBT is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MantisBT is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright 2002  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 */

/**
 * Import XML Issues Page
 */

access_ensure_project_level(plugin_config_get('import_threshold'));

auth_reauthenticate();

layout_page_header(plugin_lang_get('import'));

layout_page_begin('manage_overview_page.php');

$t_this_page = plugin_page('import'); # FIXME with plugins this does not work...
print_manage_menu($t_this_page);

$t_max_file_size = (int) min(
    ini_get_number('upload_max_filesize'),
    ini_get_number('post_max_size'),
    config_get('max_file_size')
);

# We need a project to import into
$t_project_id = helper_get_current_project();
if (ALL_PROJECTS == $t_project_id) {
    print_header_redirect('login_select_proj_page.php?ref=' . $t_this_page);
}

//get data
$t_query = 'SELECT *
        FROM frontliners
    ';
$t_result = db_query( $t_query,[] );
$t_sponsors = [];
while( $t_row = db_fetch_array( $t_result ) ) {
	$t_sponsors[] = $t_row;
}

?>
<div class = "space-10"></div>
<div class="pull-right">
	<?php print_link_button( 'plugin.php?page=UpdateBrokerAndFrontliner/createFrontliner', plugin_lang_get('create') ,'btn-md' ) ?>
</div>
<div class="col-md-12 col-xs-12">
<div class = "space-10"></div>

<div class="widget-box widget-color-blue2">
    <div class="widget-header widget-header-small">
        <h4 class="widget-title lighter">
            <i class="ace-icon fa fa-users"></i>
            <?php echo plugin_lang_get('manage_frontliner_title') ?>
            <span class="badge"><?php echo $t_total_user_count ?></span>
        </h4>
    </div>
    <div class="widget-main no-padding">
	<div class="table-responsive">
		<table id="datatable" class="table table-striped table-bordered table-condensed table-hover ">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($t_sponsors as $key => $value) { ?>
                    <tr>
                        <td><?= $value['id'] ?></td>
                        <td><?= $value['code'] ?></td>
                        <td><a href="plugin.php?page=UpdateBrokerAndFrontliner/editFrontliner&id=<?php echo $value['id'] ?>"><?php echo $value['name'] ?></a></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<?php
layout_page_end();