<?php
/* GNU GPL V2
 * A License was distributed with this plugin
 * Saturn Tables is domain trademark please remove references before commercial redistribution
 * Brimbox LLC
*/

class Saturn_Tables_Input_Form_Render extends Saturn_Tables_List_Table_Render {

    //copied from Saturn_Tables_List_Table_Extend
    public function get_menu() {

        $menus = array();
        $menus = apply_filters('saturn_tables_menus', $menus);

        foreach ($menus as $menu) {
            if (sanitize_text_field($_GET['page']) == $menu['menu_slug']) break; //menu is selected
            
        }

        return $menu;
    }

    //return column defintion from key
    public function list_table_workable($list_table_key, $item = array()) {

        $menu = $this->get_menu();

        $page = sanitize_text_field($_GET['page']);
        if (isset($_GET['paged']))
            $page_number = (int)$_GET['paged'] > 0 ? (int)$_GET['paged'] : 1;
        else $page_number = 1;
        
        if (isset($menu['callable']) && is_callable($menu['callable'])) {
            $list_table_definitions = call_user_func($menu['callable'], $page, $item, $page_number);
            if (isset($list_table_definitions) && is_array($list_table_definitions) && isset($list_table_definitions[$list_table_key]))
                return $list_table_definitions[$list_table_key];
        }
        //silent if not set
        
    }

    //return column defintion from key
    public function input_form_workable($input_form_key) {

        $menu = $this->get_menu();

        $page = sanitize_text_field($_GET['page']);

        if (isset($menu['callable']) && is_callable($menu['callable'])) {
            $input_form_definitions = call_user_func($menu['callable'], $page);
            if (isset($input_form_definitions) && is_array($input_form_definitions) && isset($input_form_definitions[$input_form_key]))
                return $input_form_definitions[$input_form_key];
        }

    }

    //copied from Saturn_Tables_List_Table_Extend
    public function input_form() {

        $workable = $this->input_form_workable('form_definition');
        if (isset($workable) && is_callable($workable)) {
            $input_form = call_user_func($workable);
        }
        
        //remove notices for pass arguments
        $query_return = false;
        $notices = "";
        $post_vars = array();

        $workable = $this->input_form_workable('run_query');
        if (isset($workable) && is_callable($workable)) {            
            //if query is not right return error or information, can use PHP validation here
            //return false on success
            $query_return = call_user_func($workable, $input_form, $query_return);
        }
        
        $workable = $this->input_form_workable('set_notices');
        if (isset($workable) && is_callable($workable)) {
            //return false on when there are no notices
            $notices = call_user_func($workable, $input_form, $query_return, $notices);
        }
        
        $workable = $this->input_form_workable('process_post_vars');
        if (isset($workable) && is_callable($workable)) {            
            //set the return state of the form
            $post_vars = call_user_func($workable, $input_form, $query_return, $post_vars);
        }        
        
        $this->render_input_form($input_form, $query_return, $notices, $post_vars);
        
    }

    public function render_input_form($input_form, $query_return, $notices, $post_vars) {

        $menus = array();
        $menus = apply_filters('saturn_tables_menus', $menus);
        
        //menu is selected
        foreach ($menus as $menu) {
            if (($page = sanitize_text_field($_GET['page'])) == $menu['menu_slug']) break;             
        }

        $input_form_definitions = call_user_func($menu['callable'], $page);
	
        echo '<div class="wrap">';			

        if (isset($input_form_definitions['menu_title'])) {
            $menu_title = $input_form_definitions['menu_title'];
        }
        elseif (!empty($menu['menu_title'])) {
            $menu_title = "<h1>" . $menu['menu_title'] . "</h1>";
        }
        if ($menu_title) {
            echo $menu_title;
            echo '<hr class="wp-header-end">';
        }

        if (isset($notices) && !empty($notices))
            if (is_array($notices))
                foreach ($notices as $value) echo $value;
            else
                echo $notices;

        echo '<form method="post" name="input_form" id="input_form" class="validate">';
        
        if (isset($input_form_definitions['header'])) {
            echo $input_form_definitions['header'];
        }
        
        //list of boolean attributes
        $arr_boolean_attrs = array("allowfullscreen","allowpaymentrequest","async","autofocus","autoplay","checked","controls","default","disabled","formnovalidate","hidden","ismap","itemscope","loop","multiple","muted","nomodule","novalidate","open","playsinline","readonly","required","reversed","selected","truespeed");

        //put hiddens at top of form so not in output table
        foreach ($input_form as $name => $input):
        
            if ($input['type'] == "hidden"):

                if (isset($post_vars[$name])) {
                    $input['value'] = $post_vars[$name];
                }
                elseif (!isset($input['value'])) {
                    $input['value'] = "";
                }
                $input['value'] = esc_html($input['value']);

                $input['name'] = $name;
                
                unset($attributes);
                
                foreach ($input as $attribute => $value) {
                    if ($value == "") {
                        $attributes[] = $attribute;
                    }
                    else {
                        $attributes[] = $attribute . '="' . $value . '"';
                    }
                }

                $tag = "<input " . implode(" ", $attributes) . "/>";

                echo $tag;                
            endif;
        endforeach;
        //end hidden
        
        //form table in Wordpress style
		echo '<table class="form-table">';
        echo '<tbody>'; 

        foreach ($input_form as $name => $input) {

            if ($input['type'] <> "hidden") {
                
                //name is array key
                $input['name'] = $name;
                if (!isset($input['id'])) $input['id'] = $name;                
                //type in array
                $label = isset($input['label']) ? $input['label'] : "";                
                //required present in array 
                $required = isset($input['required']) ? true : false;
                unset($input['label']);     

                if ($input['type'] == "textarea") {
                
                    //not an textarea attribute
                    unset($input['type']);
                
                    //inital value
                    if (isset($post_vars[$name])) {
                            $initial = $post_vars[$name];
                        }
                        elseif (isset($input['value'])) {
                            $initial = $input['value'];
                        }
                        else {
                            $initial = "";
                        }
                    unset($input['value']);            
                   
                    //prepare attributes
                    unset($attributes, $attributes_boolean, $attributes_regular);
                    $description = "";
                    foreach ($input as $attribute => $value) {
                        //attributes array
                        if (in_array($attribute, $arr_boolean_attrs)) {
                            $attributes_boolean[] = $attribute;
                        }
                        else {
                            $attributes_regular[] = $attribute . '="' . $value . '"';
                        }
                        //label
                        if ((!strcasecmp($attribute, "required"))) {
                            $description = ' <span class="description">(required)</span>';
                        } 
                    }
                    $attributes =  array_merge($attributes_regular, $attributes_boolean);                    
                    
                    $label = '<label for="' . $name . '">' . $label . $description . '</label>';        
                    $tag = "<textarea " . implode(" ", $attributes) . ">" . esc_html($initial) . "</textarea>";    

                    echo '<tr><th scope="row">' . $label . '</th><td>' . $tag . '</td></tr>';
                
                }

                elseif ($input['type'] == "select") {
                
                    //not an select attribute
                    unset($input['type']);
    
                    //not attributes
                    $options = isset($input['options']) ? $input['options'] : array();
                    $usekey = $input['usekey'];
                    unset($input['options'], $input['usekey']);
                    
                    //initial value
                    if (isset($post_vars[$name])) {
                        $initial = $post_vars[$name];
                    }
                    elseif (isset($input['value'])) {
                        $initial = $input['value'];
                    }
                    else {
                        $initial = "";
                    }
    
                    //process boolean and regular attributes
                    unset($attributes);
                    $attributes_boolean = $attributes_regular = array();
                    $description = "";
                    foreach ($input as $attribute => $value) {
                        //attributes
                        if (in_array($attribute, $arr_boolean_attrs)) {
                            $attributes_boolean[] = $attribute;
                        }
                        else {
                            $attributes_regular[] = $attribute . '="' . $value . '"';
                        }
                        //label
                        if (!strcasecmp($attribute, "required")) {
                            $description = ' <span class="description">(required)</span>';
                        }
                    }
                    $attributes =  array_merge($attributes_regular, $attributes_boolean);

                    //set up options list
                    $optionslist = "";
                    foreach ($options as $value => $option) {                                                              
                        if (isset($usekey) && $usekey) {
                            $selected = ($value == $initial) ? " selected" : "";   
                            $optionslist .= '<option value="' . esc_html($value) . '" ' . $selected . '>' . esc_html($option) . '</option>';
                        }
                        else {
                            $selected = ($option == $initial) ? " selected" : "";   
                            $optionslist .= '<option' . $selected . '>' . esc_html($option) . '</option>';
                        }
                    }    
                                    
                    $label = '<label for="' . $name . '">' . $label . $description . '</label>';        
                    $tag = "<select " . implode(" ", $attributes) . ">" . $optionslist . "</select>";    

                    echo '<tr><th scope="row">' . $label . '</th><td>' . $tag . '</td></tr>';
                    
                    }

                else {
    
                    if (isset($post_vars[$name])) {
                        $input['value'] = $post_vars[$name];
                    }
                    elseif (!isset($input['value'])) {
                        $input['value'] = "";
                    }
                    
                    //htmlentities
                    $input['value'] = esc_html($input['value']);
    
                    unset($attributes);
                    $attributes_boolean = $attributes_regular = array();
                    $description = "";
                    foreach ($input as $attribute => $value) {
                        //attributes
                        if (in_array($attribute, $arr_boolean_attrs)) {
                            $attributes_boolean[] = $attribute;
                        }
                        else {
                            $attributes_regular[] = $attribute . '="' . $value . '"';
                        }                        
                        //label
                        if (!strcasecmp($attribute, "required")) {
                            $description = ' <span class="description">(required)</span>';
                        } 
                    }
                    
                    $attributes =  array_merge($attributes_regular, $attributes_boolean);
            
                                       
                    $tag =  '<input ' . implode(" ", $attributes) . '/>';                                  
                    $label = '<label for="' . $name . '">' . $label . $description . '</label>';                    
                    echo '<tr><th scope="row">' . $label . '</th><td>' . $tag . '</td></tr>';
        
                }
            }
        }
        
        echo '</tbody></table>';
        
        if (isset($input_form_definitions['submit_button'])) {
            $submit_button = $input_form_definitions['submit_button'];
        }
        
        $text = isset($submit_button['text']) ? $submit_button['text'] : "Input";
        $type = isset($submit_button['type']) ? $submit_button['type'] : "button-primary";
        $name = isset($submit_button['name']) ? $submit_button['name'] : "submit";
        $wrap = !empty($submit_button['wrap']) ? true : false;
        $other_attributes = isset($submit_button['other_attributes']) ? $submit_button['other_attributes'] : array();

        //Wordpress Submit button
        submit_button($text, $type, $name, $wrap, $other_attributes);

        if (isset($input_form_definitions['footer'])) {
            echo $input_form_definitions['footer'];
        }

        echo '</form>';        
        echo '</div>';

    }

}

?>