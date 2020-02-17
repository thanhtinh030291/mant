<?php
/**
LastEditorColumn
Original version copyright 2010, Brian Enigma, <http://netninja.com/projects/tagcolumn/>

Code for improved CSV, Excel export contributed by Albie Janse van Rensburg, December 2013

Edited to show last editor instead of tags by Matthias Blümel, July 2016

LastEditorColumn is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

LastEditorColumn is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

*/

/**
 * requires columns_api
 */
require_api( 'columns_api.php' );

// Kind of an awkward name because we don't want to conflict with a native Mantis class if they ever add one.
class LastEditorColumnPluginColumn extends MantisColumn
{
    public $title = "Last editor";
    public $column = "LastEditor";
    public $sortable = false;
    public function sortquery( $p_dir ) {}
    // In an ideal world, we'd take the bug IDs and do a single query joining bug IDs to tags, then cache them.
    public function cache( $p_bugs ) {}
    // In an ideal world, we'd use the cache() function, above, instead of lots of calls to tag_bug_get_attached (which hits the database each call)
    public function display( BugData $p_bug, $p_columns_target )
    {
        $t_query = 'SELECT user_id FROM {bug_history} WHERE bug_id=' . db_param() . ' ORDER BY date_modified DESC, id DESC LIMIT 0,1';
        $t_params = array( $p_bug->id );
        $t_result = db_query( $t_query, $t_params );
        $t_row = db_fetch_array( $t_result );
        extract( $t_row, EXTR_PREFIX_ALL, 'v' );
        print user_get_name( $v_user_id );
    }
}

class LastEditorColumnPlugin extends MantisPlugin 
{
    function register() 
    {
        $this->name        = 'LastEditor Column';
        $this->description = 'Adds a column within the View Issues screen showing the last editor.';
        $this->version     = '1.0.3';
        $this->author      = 'Matthias Blümel';
        $this->contact     = 'matthias.bluemel@krumedia.com';
        $this->url         = 'http://www.krumedia.com';
        $this->requires['MantisCore'] = '1.3, 2.0, 2.2';
    }
 
    function init() 
    {
        plugin_event_hook( 'EVENT_FILTER_COLUMNS', 'addColumn' );
    }

    function addColumn()
    {
        return array('LastEditorColumnPluginColumn');
    }
}

