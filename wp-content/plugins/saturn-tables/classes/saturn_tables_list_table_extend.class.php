<?php

/* GNU GPL V2
 * A License was distributed with this plugin
 * Saturn Tables is domain trademark please remove references before commercial redistribution
 * Brimbox LLC
*/


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Saturn_Tables_List_Table_Extend extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( array(
			'ajax'     => false //does this table support ajax?
		) );
		
					
		$this->process_link_actions();
		$this->process_bulk_actions();
		$this->admin_notices();

	}
	
	//return menu	
	public function get_menu() {
		
		$menus = array();
		$menus = apply_filters('saturn_tables_menus', $menus);
		
		foreach ($menus as $menu) {
			if (sanitize_text_field($_GET['page']) == $menu['menu_slug'])
				break; //menu is selected
		}		

		return $menu;
	}
	
	//return column defintion from key
	public function list_table_workable($list_table_key, $item = array()) {
		
		$menu = $this->get_menu();
		
		$page = sanitize_text_field($_GET['page']);
		if (isset($_GET['paged']))
			$page_number = (int)$_GET['paged'] > 0 ? (int)$_GET['paged'] : 1;
		else
			$page_number = 1;
			
		if (isset($menu['callable']) && is_callable($menu['callable'])) {
			$list_table_definitions = call_user_func($menu['callable'], $page, $item, $page_number);
			if (isset($list_table_definitions) && is_array($list_table_definitions) && isset($list_table_definitions[$list_table_key]))
				return $list_table_definitions[$list_table_key];
		}
		//silent if not set
	}
	

	public function get_items( $per_page, $page_number ) {
		
		$workable = $this->list_table_workable('get_data');
		
		if (isset($workable) && is_callable($workable))		
			$data = call_user_func($workable, $per_page, $page_number);
		else
			$data = array();

		return $data;
	}
	
		//get record count
	public function record_count() {
		
		$workable = $this->list_table_workable('get_data_count');
		
		if (isset($workable) && is_callable($workable))			
			$count = call_user_func($workable);
		else
			$count = 0;

		return $count;
	}

	
	//get views
	protected function get_views() {		
		
		$views = $this->list_table_workable('views');
		
		if (isset($views) && is_array($views))	
			return $views;
	}

	//get dropdowns
	function extra_tablenav( $which ) {
		
		$workable1 = $this->list_table_workable('extra_navigation');
		$workable2 = $this->list_table_workable('filter_button');
		
		if (isset($workable1) && is_callable($workable1)) {
			if ( $which == "top" ){
			   echo '<div class="alignleft actions">';
			   echo call_user_func($workable1);
			   if (isset($workable2) && is_array($workable2)) {
					$text = isset($workable2['text']) ? $workable2['text'] : "Filter";
					$type = isset($workable2['type']) ? $workable2['type'] : "button";
					$name = isset($workable2['name']) ? $workable2['name'] : "submit";
					$wrap = !empty($workable2['wrap']) ? true : false;
					$other_attributes = isset($workable2['other_attributes']) ? $workable2['other_attributes'] : array();
				
					submit_button( $text, $type, $name , $wrap, $other_attributes );
					
					if ($this->log)
						saturn_tables_log ("list_table >> extra_navigation", $workable1);

					if ($this->log)
						saturn_tables_log ("list_table >> filter_button", $workable2);

			   }
			   echo '</div>';
			   
			}
			if ( $which == "bottom" ){
			   //The code that goes after the table is there
			   
			}
		}
	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		
		_e( 'No Data available.', 'saturn-tables' );
	}

	
	//columns filter
	public function column_default( $item, $column_name ) {
		
		$columns = $this->list_table_workable('columns', $item);		
		$actions = $this->list_table_workable('link_actions', $item);
		$markup = $this->list_table_workable('column_markup', $item);
		
		if (isset($columns) && is_array($columns) && isset($columns[$column_name])) {
			
			if (isset($markup) && is_array($markup) && isset($markup[$column_name])) 
				$data = $markup[$column_name];
			else 
				$data = $item[$column_name];	

			if (isset($actions) && is_array($actions) && isset($actions[$column_name]))
				return $data .  $this->row_actions( $actions[$column_name] );
			else 
				return $data;
		}

	}
	
	//columns filter
	function column_cb( $item ) {
		
		$checkbox_id = $this->list_table_workable('checkbox_id', $item);

		if (isset($checkbox_id) && is_string($checkbox_id))
			return sprintf('<input type="checkbox" name="cb[]" value="%s" />', $item[$checkbox_id]);
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	
	//columns filter
	function get_columns() {
		
		$columns =  $this->list_table_workable('columns');
		
		if (isset($columns) && is_array($columns))
			return $columns;
		
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	
	//columns filter
	public function get_sortable_columns() {
		
		$sortable =  $this->list_table_workable('sortable');
				
		//this must return an array
		if (isset($sortable) && is_array($sortable))
			return $sortable;
		else
			return array(); //core requires and empty array
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		
		$bulk_actions = $this->list_table_workable('bulk_actions');
				
		if (isset($bulk_actions) && is_array($bulk_actions))
			return $bulk_actions;
	
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();
		
		$user = get_current_user_id();
		$screen = get_current_screen();
		$option = $screen->get_option('per_page', 'option');
		 
		$per_page = get_user_meta($user, $option, true);
		 
		if ( empty ( $per_page) || $per_page < 1 ) { 
			$per_page = $screen->get_option( 'per_page', 'default' ); 
		}

		$current_page = $this->get_pagenum();
		$total_items  = $this->record_count();

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items/$per_page) //WE have to determine how many items to show on a page
		) );

		$this->items = $this->get_items($per_page, $current_page);
	}

	public function process_link_actions() {
		
		$process_link_actions =  $this->list_table_workable('process_link_actions');
				
		if (isset($process_link_actions) && is_callable($process_link_actions))
			call_user_func($process_link_actions);
		
		
	}
    
	public function process_bulk_actions() {
		
		$process_bulk_actions =  $this->list_table_workable('process_bulk_actions');
				
		if (isset($process_bulk_actions) && is_callable($process_bulk_actions))
			call_user_func($process_bulk_actions);
	

	}
	
	public function admin_notices() {
		
		$process_notices =  $this->list_table_workable('notices');
			
		if (isset($process_notices) && is_callable($process_notices))
			add_action( 'admin_notices', $process_notices);	

	 }
	 
	public function display_tablenav( $which ) {
		
		//extended to remove nonce
		if ( 'top' === $which ) {
			//wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
	
			<?php if ( $this->has_items() ): ?>
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
		?>
		<br class="clear" />
		</div>
	<?php
	}
}
?>