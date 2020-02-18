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




access_ensure_project_level(plugin_config_get('import_threshold'));

auth_reauthenticate();

layout_page_header(plugin_lang_get('import'));

layout_page_begin('manage_overview_page.php');

$t_this_page = plugin_page('import'); # FIXME with plugins this does not work...
print_manage_menu($t_this_page);


$t_id = gpc_get_string('id');
//get data
$t_query = 'SELECT 
            br.id , name, code , user_id, project_id
        FROM brokers br
        INNER JOIN broker_project bj
        ON br.broker_project_id = bj.id
        WHERE br.id =' .db_param();

$t_result = db_query( $t_query,[$t_id] );
$t_sponsors = [];
while( $t_row = db_fetch_array( $t_result ) ) {
	$t_sponsors[] = $t_row;
}

//get data all user
$t_query = 'SELECT *
        FROM {project}' ;
$t_result = db_query( $t_query,[] );
$t_projects = [];
while( $t_row = db_fetch_array( $t_result ) ) {
	$t_projects[] = $t_row;
}
//get data all project 
?>

<div class="col-md-12 col-xs-12">
	<div class="space-10"></div>

<!-- USER INFO -->
<div id="edit-user-div" class="form-container">
	<form id="edit-user-form" method="post" action="plugin.php?page=UpdateBrokerAndFrontliner/editBrokerAction">
		<div class="widget-box widget-color-blue2">
			<div class="widget-header widget-header-small">
				<h4 class="widget-title lighter">
					<i class="ace-icon fa fa-user"></i>
					<?php echo plugin_lang_get('manage_broker_title') ?>
				</h4>
			</div>
		<div class="widget-body">
		<div class="widget-main no-padding">
		<div class="form-container">
		<div class="table-responsive">
		<table class="table table-bordered table-condensed table-striped">
		<fieldset>
			<?php echo form_security_field( 'manage_boker_update' ) ?>
			<!-- Title -->
			<input type="hidden" name="id" value="<?php echo $t_sponsors[0]['id'] ?>" />

			<!-- name -->
			<tr>
				<td class="category">
					<?php echo plugin_lang_get( 'broker_name' ) ?>
				</td>
				<td>
					<input type="text" class="input-sm" size="32" name="name" value="<?php echo $t_sponsors[0]['name'] ?>" />
				</td>
			</tr>

            <!-- code -->
			<tr>
				<td class="category">
					<?php echo plugin_lang_get( 'broker_code' ) ?>
				</td>
				<td>
					<input type="text" class="input-sm" size="32" name="code" value="<?php echo $t_sponsors[0]['code'] ?>" />
				</td>
			</tr>

            <!-- broker project id -->
			<tr>
				<td class="category">
					<?php echo plugin_lang_get( 'project_id' ) ?>
				</td>
				<td>
					<select id='userSelect' class="select" size="32" name="user_id" >
                        <?php foreach ($t_users as $key => $value) { ?>
                            <option value="<?= $value['id']?>" <?= $t_sponsors[0]['user_id'] == $value['id'] ? 'selected' : "" ?>> <?= $value['username']?> </option>
                        <?php } ?>
                    </select>

                    <select id='projectSelect' class="select" size="32" name="project_id" >
                        <?php foreach ($t_projects as $key => $value) { ?>
                            <option value="<?= $value['id']?>" <?= $t_sponsors[0]['project_id'] == $value['id'] ? 'selected' : "" ?>> <?= $value['username']?> </option>
                        <?php } ?>
                    </select>
				</td>
			</tr>
			<!-- Submit Button -->
		</fieldset>
		</table>
	
		<div class="widget-toolbox padding-8 clearfix">
			<input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo plugin_lang_get( 'submit' ) ?>" />
		</div>
	</form>
    <div class="widget-toolbox padding-8 clearfix">
        <form  method="post" action="plugin.php?page=UpdateBrokerAndFrontliner/deleteBrokerAction">
            <input type="hidden" name="id" value="<?php echo $t_sponsors[0]['id'] ?>" />
            <input type="submit" class="btn btn-danger " value="<?php echo plugin_lang_get( 'delete' ) ?>" />
        </form>
    </div>
</div>
<div class="space-10"></div>

<?php

layout_page_end();