<?php

class Select2Plugin extends MantisPlugin
{
    function register()
    {
        $this->name        = 'Select2';
        $this->description = 'Select2';
        $this->version     = '1.0';
        $this->author      = 'Select2';
        $this->requires['MantisCore'] = '2.0';
    }

    function hooks()
    {
		return [
			'EVENT_LAYOUT_RESOURCES' => 'onAddScript'
		];
	}

    function onAddScript( $p_event )
    {
        echo '<link rel="stylesheet" type="text/css" href="' . plugin_file( 'css/select2.min.css' ) . '" />';
        echo '<link rel="stylesheet" type="text/css" href="' . plugin_file( 'css/select2-bootstrap.min.css' ) . '" />';
        echo '<script type="text/javascript" src="' . plugin_file( 'js/select2.full.min.js' ) . '"></script>';
        echo '<script type="text/javascript" src="' . plugin_file( 'select2.js' ) . '"></script>';
    }
}
