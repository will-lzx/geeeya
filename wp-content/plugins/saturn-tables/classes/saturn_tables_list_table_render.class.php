<?php

/* GNU GPL V2
 * A License was distributed with this plugin
 * Saturn Tables is domain trademark please remove references before commercial redistribution
 * Brimbox LLC
*/


class Saturn_Tables_List_Table_Render {
	
	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen' ), 10, 3 );
		add_action( 'admin_menu', array(  $this, 'plugin_menu' ));
	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function plugin_menu() {		
		
		$saturn_tables_menus = array();
		$saturn_tables_menus = apply_filters('saturn_tables_menus', $saturn_tables_menus);
		
		if (!empty($saturn_tables_menus)) :
		
			foreach ($saturn_tables_menus as $menu) :
			
			if ($menu['type'] == 'list_table') {
				
				if (key_exists('parent_slug', $menu)) {			
						$hook = add_submenu_page($menu['parent_slug'], $menu['page_title'], $menu['menu_title'], $menu['capability'], $menu['menu_slug'], [ $this, 'list_table_render' ] );				
				}
				else {			
						$hook = add_menu_page($menu['page_title'], $menu['menu_title'], $menu['capability'], $menu['menu_slug'], [ $this, 'list_table_render' ], $menu['icon_url'], $menu['position']	);
				}
				
				//important from line 67
				add_action( "load-$hook", [ $this, 'screen_option' ] );
				
			} elseif ($menu['type'] == 'input_form') {
				if (key_exists('parent_slug', $menu)) {			
						$hook = add_submenu_page($menu['parent_slug'], $menu['page_title'], $menu['menu_title'], $menu['capability'], $menu['menu_slug'], [ $this, 'input_form' ] );				
				}
				else {			
						$hook = add_menu_page($menu['page_title'], $menu['menu_title'], $menu['capability'], $menu['menu_slug'], [ $this, 'input_form' ], $menu['icon_url'], $menu['position']	);
				}				
			}
			else {
				if ($menu['type'] == 'submenu') {			
					
					add_submenu_page($menu['parent_slug'], $menu['page_title'], $menu['menu_title'], $menu['capability'], $menu['menu_slug'], $menu['callable'] );
					
				} elseif ($menu['type'] == 'menu') {
				
					add_menu_page($menu['page_title'], $menu['menu_title'], $menu['capability'], $menu['menu_slug'], $menu['callable'], $menu['icon_url'], $menu['position']	);
				
				}		
	
			}
		
			endforeach;
		
		endif;
		
	}


	/**
	 * Plugin settings page
	 */
	
	
	public function list_table_render() {
		
		$menus = array();
		$menus = apply_filters('saturn_tables_menus', $menus);
		
		foreach ($menus as $menu) {
			if (($page = sanitize_text_field($_GET['page'])) == $menu['menu_slug'])
				break; //menu is selected
		}	
        
		$list_table_definitions = call_user_func($menu['callable'], $page);
	 
		?>
		
		<div class="wrap">
			<?php
			$menu_title = $this->list_table_workable('menu_title');
			if (isset($menu_title) && is_string($menu_title)) {
				echo $menu_title;
			} elseif (isset($menu['menu_title']) && is_string($menu['menu_title']))  {
				echo "<h1>" . $menu['menu_title'] . "</h1>";
			}			
			?>
			<hr class="wp-header-end">			
			<form id="posts-filter" method="get">
				<input type="hidden" name="page" value="<?php echo esc_html($_GET['page']); ?>">
				<?php
				$header = $this->list_table_workable('header');
				if (isset($header) && is_string($header)) {
						echo $header;
				}
				
				$this->list_table_obj->prepare_items();
				$this->list_table_obj->views();
				$search = $this->list_table_workable('search');
				if (isset($search) && is_array($search)) {
					$search_label = isset($search['label']) ? $search['label'] : "Search";
					$search_id = isset($search['id']) ? $search['id'] : "search_id";
					$this->list_table_obj->search_box($search_label, $search_id);
				}
				$this->list_table_obj->display();
				
				$footer = $this->list_table_workable('footer');
				if (isset($footer) && is_string($footer)) {
					echo $footer;
				}
				?>
			</form>

			<div class="clear"></div>
	
		</div>
	<?php
	}

	/**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Per Page',
			'default' => 50,
			'option'  => 'per_page'
		];

		add_screen_option( $option, $args );

		$this->list_table_obj = new Saturn_Tables_List_Table_Extend();
	}

}

?>