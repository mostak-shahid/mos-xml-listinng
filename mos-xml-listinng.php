<?php
/*
Plugin Name: Mos Realty Property Listing from XML
Description: This is a Plugin for Guardian Group Realty Property Listing. How to use it, very simple just active this plugin, put your XML files in the upload folder the plugin will search for new file every time the site will load, if it get a new file it will upload file data to your project section.
Version: 0.0.1
Author: Md. Mostak Shahid
*/
require_once( ABSPATH . "wp-includes/pluggable.php" ); 
require_once ( plugin_dir_path( __FILE__ ) . 'mos-xml-listinng-functions.php' ); 
register_activation_hook(__FILE__, 'after_plugin_active');
global $dir;
$dir = '../wp-content/uploads';
function after_plugin_active() {	
	global $dir;
	if(!is_dir($dir)) {
		mkdir($dir);
	}
}

// if (is_admin()) {
// 	$files = dirToArray($dir);
// 	foreach ($files as $file) {		
// 		if (check_file_ext(array('xml', 'XML'), $file) AND check_new_file ($file)) {
// 			store_data ($file);
// 			update_file_name ($file);
// 		}
// 	}
// }




