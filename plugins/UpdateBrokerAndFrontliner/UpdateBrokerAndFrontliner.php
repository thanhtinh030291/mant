<?php

class UpdateBrokerAndFrontlinerPlugin extends MantisPlugin
{
    
    function register()
    {
        $this->name        = 'UpdateBrokerAndFrontliner';
        $this->description = 'Adds Broker or Frontliner to mantis_custom_field_table functions.';
        $this->version     = '1.0.0';
        $this->author      = 'Tinh Nguyen';
        $this->contact     = 'tinhnguyen@pacificcross.com.vn';
        $this->url         = 'http://pacificcross.com.vn';
        $this->requires['MantisCore'] = '2.0';
    }

    function init()
    {
        plugin_event_hook( 'EVENT_LAYOUT_RESOURCES', 'onAddScript' );
        plugin_event_hook( 'EVENT_MENU_MANAGE', 'import_issues_menu' );
        
    }

    function onAddScript( $p_event )
    {
        echo "<style>
            .uppercase {
                text-transform: uppercase;
                }
        </style>";
        
    }

    function import_issues_menu() {
        return [ 
            '<a href="' . plugin_page( 'indexBroker' ) . '">' . plugin_lang_get('manage_broker_title') . '</a>', 
            '<a href="' . plugin_page( 'indexFrontliner' ) . '">' . plugin_lang_get('manage_frontliner_title') . '</a>', 
        ];
        
	}
}
