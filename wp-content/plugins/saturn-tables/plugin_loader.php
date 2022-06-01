<?php
/*
Plugin Name: Saturn Tables
Plugin URI: https://saturntables.com
Description: A List Table creation plugin to enable datasources and data tables to be displayed in the WordPress administration panel in the native dashboard tabular format.
Version: 1.2.5
Author: Brimbox LLC
License: GPL v2.0
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


if( is_admin() ) {
	
	if (is_dir(get_stylesheet_directory() . "/saturn-tables/")) {
		foreach (glob(get_stylesheet_directory() . "/saturn-tables/*.php") as $saturn_tables_filename) {
				include_once($saturn_tables_filename);
		}
	}
	
	//Include Classes
	include( plugin_dir_path( __FILE__ ) . 'classes/saturn_tables_list_table_extend.class.php');
	include( plugin_dir_path( __FILE__ ) . 'classes/saturn_tables_list_table_render.class.php');
	include( plugin_dir_path( __FILE__ ) . 'classes/saturn_tables_input_form_render.class.php');
	
	//singleton to render list tables and input forms
	new Saturn_Tables_Input_Form_Render();

}	

?>