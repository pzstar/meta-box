<?php

/**
 * Custom Meta Box Class
 */
if (!class_exists('HashThemes_Meta_Box')) {

    /**
     * All Types Meta Box class.
     *
     * @package All Types Meta Box
     *
     * @todo Nothing.
     */
    class HashThemes_Meta_Box {

        /**
         * Holds meta box object
         *
         * @var object
         * @access protected
         */
        protected $_meta_box;

        /**
         * Holds meta box fields.
         *
         * @var array
         * @access protected
         */
        protected $_prefix;

        /**
         * Holds Prefix for meta box fields.
         *
         * @var array
         * @access protected
         */
        protected $_fields;

        /**
         * SelfPath to allow themes as well as plugins.
         *
         * @var string
         * @access protected
         */
        protected $SelfPath;

        /**
         * $field_types  holds used field types
         * @var array
         * @access public
         */
        public $field_types = array();

        /**
         * $inGroup  holds groupping boolean
         * @var boolean
         * @access public
         */
        public $inGroup = false;

        /**
         * Constructor
         *
         * @access public
         *
         * @param array $meta_box 
         */
        public function __construct($meta_box) {

            // If we are not in admin area exit.
            if (!is_admin())
                return;

            // Assign meta box values to local variables and add it's missed values.
            $this->_meta_box = $meta_box;
            $this->_prefix = (isset($meta_box['prefix'])) ? $meta_box['prefix'] : '';
            $this->_fields = $this->_meta_box['fields'];
            $this->add_missed_values();
            if (isset($meta_box['use_with_theme'])) {
                if ($meta_box['use_with_theme'] === true) {
                    $this->SelfPath = get_stylesheet_directory_uri() . '/inc/meta-box/';
                } elseif ($meta_box['use_with_theme'] === false) {
                    $this->SelfPath = plugins_url('meta-box-class', plugin_basename(dirname(__FILE__)));
                } else {
                    $this->SelfPath = $meta_box['use_with_theme'];
                }
            }

            // Add metaboxes
            add_action('add_meta_boxes', array($this, 'add'));
            add_action('save_post', array($this, 'save'));
            // Load common js, css files
            add_action('admin_enqueue_scripts', array($this, 'load_scripts_styles'));
        }

        /**
         * Load all Javascript and CSS
         *
         * @access public
         */
        public function load_scripts_styles() {
            // Get Plugin Path
            $plugin_path = $this->SelfPath;

            //only load styles and js when needed
            global $typenow;
            if (in_array($typenow, $this->_meta_box['pages']) && $this->is_edit_page()) {
                // Enqueue Meta Box Style
                wp_enqueue_style('hashthemes-meta-box', $plugin_path . 'assets/meta-box.css');

                // Enqueue Meta Box Scripts
                wp_enqueue_script('hashthemes-meta-box', $plugin_path . 'assets/meta-box.js', array('jquery'), null, true);

                // Check for special fields and add needed actions for them
                foreach (array('color', 'alpha_color', 'date', 'time', 'select') as $type) {
                    call_user_func(array($this, 'check_field_' . $type));
                }
            }
        }

        /**
         * Check the Field select, Add needed Actions
         *
         * @access public
         */
        public function check_field_select() {

            // Check if the field is an image or file. If not, return.
            if (!$this->has_field('select') && !$this->has_field('widget_list') && !$this->has_field('taxonomy') && !$this->has_field('posts'))
                return;
            $plugin_path = $this->SelfPath;
            wp_enqueue_style('select2', $plugin_path . '/assets/select2/css/select2.css', array(), null);
            wp_enqueue_script('select2', $plugin_path . '/assets/select2/js/select2.js', array('jquery'), false, true);
        }

        /**
         * Check Field Color
         *
         * @access public
         */
        public function check_field_color() {

            if ($this->has_field('color') && $this->is_edit_page()) {
                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script('wp-color-picker');
            }
        }

        /**
         * Check Field Alpha Color
         *
         * @access public
         */
        public function check_field_alpha_color() {
            $plugin_path = $this->SelfPath;
            if ($this->has_field('color') && $this->is_edit_page()) {
                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script('wp-color-picker');
                wp_enqueue_script('wp-color-picker-alpha', $plugin_path . '/assets/wp-color-picker-alpha.js', array('jquery', 'wp-color-picker'), false, true);
            }
        }

        /**
         * Check Field Date
         *
         * @access public 
         */
        public function check_field_date() {

            if ($this->has_field('date') && $this->is_edit_page()) {
                $plugin_path = $this->SelfPath;
                wp_enqueue_style('jquery-ui', $plugin_path . '/assets/jquery-ui/jquery-ui.css');
                wp_enqueue_script('jquery-ui');
                wp_enqueue_script('jquery-ui-datepicker');
            }
        }

        /**
         * Check Field Time
         *
         * @access public
         */
        public function check_field_time() {

            if ($this->has_field('time') && $this->is_edit_page()) {
                $plugin_path = $this->SelfPath;
                wp_enqueue_style('jquery-ui', $plugin_path . '/assets/jquery-ui/jquery-ui.css');
                wp_enqueue_script('jquery-ui');
                wp_enqueue_script('jquery-ui-timepicker-addon', $plugin_path . '/assets/jquery-ui/jquery-ui-timepicker-addon.js', array('jquery-ui-slider', 'jquery-ui-datepicker'), false, true);
            }
        }

        /**
         * Add Meta Box for multiple post types.
         *
         * @access public
         */
        public function add($postType) {
            if (in_array($postType, $this->_meta_box['pages'])) {
                add_meta_box($this->_meta_box['id'], $this->_meta_box['title'], array($this, 'show'), $postType, $this->_meta_box['context'], $this->_meta_box['priority']);
            }
        }

        /**
         * Callback function to show fields in meta box.
         *
         * @access public 
         */
        public function show() {
            $this->inGroup = false;
            $tab = $content = '';
            $active_tab = true;
            $id = $this->_meta_box['id'];
            global $post;

            wp_nonce_field(basename(__FILE__), $id . '_meta_box_nonce');
            echo '<div class="ht--meta-box-container">';

            foreach ($this->_fields as $field) {
                ob_start();
                if ($field['type'] == 'tabopen') {
                    $active_class = $active_tab ? ' ht--active-tab' : '';
                    $display_status = $active_tab ? 'style="display:block"' : '';

                    $tab .= "<li class='ht--meta-box-tab" . esc_attr($active_class) . "' data-panel='" . esc_attr($field['id']) . "'><a href='#'>";
                    if (isset($field['icon'])) {
                        $tab .= "<i class='dashicons-admin-generic dashicons'></i>";
                    }

                    if (isset($field['name'])) {
                        $tab .= esc_html($field['name']);
                    }
                    $tab .= "</a></li>";
                    $content .= '<div class="ht--meta-box-panel ' . esc_attr($field['id']) . '" ' . $display_status . '>';
                    $active_tab = false;
                } elseif ($field['type'] == 'tabclose') {
                    $content .= '</div>';
                } else {
                    $field['multiple'] = isset($field['multiple']) ? $field['multiple'] : false;
                    $meta = get_post_meta($post->ID, $field['id'], !$field['multiple']);
                    $default_value = isset($field['std']) ? $field['std'] : '';
                    $meta = ( $meta !== '' ) ? $meta : $default_value;
                    $meta_box_class = isset($field['label_block']) && $field['label_block'] ? ' ht--meta-box-label-block' : '';

                    if (!in_array($field['type'], array('image', 'repeater', 'cond'))) {
                        $meta = is_array($meta) ? array_map('esc_attr', $meta) : esc_attr($meta);
                    }
                    if (!isset($field['group']) && $this->inGroup !== true) {
                        echo '<div class="ht--meta-box-row ht--meta-box-' . esc_attr($field['type']) . $meta_box_class . '">';
                    }
                    if (isset($field['group']) && $field['group'] == 'start') {
                        $this->inGroup = true;
                        echo '<div class="ht--meta-box-row' . esc_attr($meta_box_class) . '">';
                        echo '<table class="ht--meta-box-form-table"><tr>';
                    }

                    // Call Separated methods for displaying each type of field.
                    call_user_func(array($this, 'show_field_' . $field['type']), $field, $meta);

                    if ($this->inGroup === true) {
                        if (isset($field['group']) && $field['group'] == 'end') {
                            echo '</tr></table></div>';
                            $this->inGroup = false;
                        }
                    } else {
                        echo '</div>';
                    }
                    $content .= ob_get_contents();
                }
                ob_end_clean();
            }

            if ($tab) {
                echo '<ul class="ht--meta-box-tab-nav">' . $tab . '</ul>';
            }

            echo '<div class="ht--meta-box-panels">' . $content . '</div>';
            echo '</div>';
        }

        /**
         * Show Repeater Fields.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_repeater($field, $meta) {
            global $post;
            // Get Plugin Path
            $this->show_field_begin($field, $meta);
            $class = '';
            if ($field['sortable'])
                $class = " ht--repeater-sortable";
            echo "<div class='ht--repeater" . esc_attr($class) . "' id='" . esc_attr($field['id']) . "'>";

            $c = 0;
            $meta = get_post_meta($post->ID, $field['id'], true);

            if (count($meta) > 0 && is_array($meta)) {
                foreach ($meta as $me) {
                    //for labling toggles
                    $mmm = isset($me[$field['fields'][0]['id']]) ? $me[$field['fields'][0]['id']] : "";
                    if (in_array($field['fields'][0]['type'], array('image', 'file')))
                        $mmm = $c + 1;
                    echo '<div class="ht--repater-block">';
                    echo '<h6>';
                    if ($field['sortable']) {
                        echo '<span class="ht--re-control ht--re-sort-handle"><span class="dashicons dashicons-move"></span></span>';
                    }
                    echo $mmm;
                    echo '<span class="ht--re-control ht--re-toggle"><span class="dashicons dashicons-arrow-down"></span></span>';
                    echo '</h6>';
                    echo '<div class="ht--meta-box-repeater-table">';
                    echo '<span class="ht--re-control ht--re-remove" id="ht--remove-' . esc_attr($field['id']) . '"><span class="dashicons dashicons-dismiss"></span></span>';
                    if ($field['inline']) {
                        echo '<table class="ht--meta-box-form-table">';
                        echo '<tr VALIGN="top">';
                    }
                    foreach ($field['fields'] as $f) {
                        //reset var $id for repeater
                        $id = '';
                        $id = $field['id'] . '[' . $c . '][' . $f['id'] . ']';
                        $m = isset($me[$f['id']]) ? $me[$f['id']] : '';
                        $m = ( $m !== '' ) ? $m : $f['std'];
                        if ('image' != $f['type'] && $f['type'] != 'repeater') {
                            $m = is_array($m) ? array_map('esc_attr', $m) : esc_attr($m);
                        }
                        //set new id for field in array format
                        $f['id'] = $id;
                        $meta_box_class = isset($f['label_block']) && $f['label_block'] ? ' ht--meta-box-label-block' : '';
                        if ($field['inline']) {
                            echo '<td>';
                        }
                        echo '<div class="ht--meta-box-row ht--meta-box-' . esc_attr($f['type']) . esc_attr($meta_box_class) . '">';
                        call_user_func(array($this, 'show_field_' . $f['type']), $f, $m);
                        echo '</div>';
                        if ($field['inline']) {
                            echo '</td>';
                        }
                    }
                    if ($field['inline']) {
                        echo '</tr>';
                        echo '</table>';
                    }

                    echo '</div>';
                    echo '</div>';
                    $c = $c + 1;
                }
            }

            echo '<button class="button" id="add-' . esc_attr($field['id']) . '">' . esc_html__('+ Add', 'hashthemes') . '</button>';
            echo '</div>';

            //create all fields once more for js function and catch with object buffer
            ob_start();
            echo '<div class="ht--repater-block">';
            echo '<h6>';
            if ($field['sortable']) {
                echo '<span class="ht--re-control ht--re-sort-handle"><span class="dashicons dashicons-move"></span></span>';
            }
            echo 'List';
            echo '<span class="ht--re-control ht--re-toggle"><span class="dashicons dashicons-arrow-down"></span></span>';
            echo '</h6>';
            echo '<div class="ht--meta-box-repeater-table">';
            echo '<span class="ht--re-control ht--re-remove" id="ht--remove-' . esc_attr($field['id']) . '"><span class="dashicons dashicons-dismiss"></span></span>';
            if ($field['inline']) {
                echo '<table class="ht--meta-box-form-table">';
                echo '<tr VALIGN="top">';
            }
            foreach ($field['fields'] as $f) {
                //reset var $id for repeater
                $id = '';
                $id = $field['id'] . '[CurrentCounter][' . $f['id'] . ']';
                $f['id'] = $id;
                $meta_box_class = isset($f['label_block']) && $f['label_block'] ? ' ht--meta-box-label-block' : '';
                if ($field['inline']) {
                    echo '<td>';
                }
                echo '<div class="ht--meta-box-row ht--meta-box-' . esc_attr($f['type']) . esc_attr($meta_box_class) . '">';
                if ($f['type'] != 'wysiwyg')
                    call_user_func(array($this, 'show_field_' . $f['type']), $f, '');
                else
                    call_user_func(array($this, 'show_field_' . $f['type']), $f, '', true);
                echo '</div>';
                if ($field['inline']) {
                    echo '</td>';
                }
            }
            if ($field['inline']) {
                echo '</tr>';
                echo '</table>';
            }
            
            echo '</div>';
            echo '</div>';

            $counter = 'countadd_' . $field['id'];
            $js_code = ob_get_clean();
            $js_code = str_replace("\n", "", $js_code);
            $js_code = str_replace("\r", "", $js_code);
            $js_code = str_replace("'", "\"", $js_code);
            $js_code = str_replace("CurrentCounter", "' + " . $counter . " + '", $js_code);
            echo '<script>
            jQuery(document).ready(function() {
                var ' . $counter . ' = ' . $c . ';
                jQuery("#add-' . $field['id'] . '").on(\'click\', function() {
                  ' . $counter . ' = ' . $counter . ' + 1;
                  jQuery(this).before(\'' . $js_code . '\');            
                  update_repeater_fields();
                });
            });
            </script>';
            $this->show_field_end($field, $meta);
        }

        /**
         * Begin Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_begin($field, $meta) {
            if ($this->inGroup === true) {
                $meta_box_class = isset($field['label_block']) && $field['label_block'] ? ' ht--meta-box-label-block' : '';
                echo "<td>";
                echo '<div class="ht--meta-box-row ht--meta-box-' . esc_attr($field['type']) . esc_attr($meta_box_class) . '">';
            }
            if ($field['name'] != '' || $field['name'] != FALSE) {
                echo "<div class='ht--meta-box-label'>";
                echo "<label for='" . esc_attr($field['id']) . "'>" . esc_attr($field['name']) . "</label>";
                echo "</div>";
                echo "<div class='ht--meta-box-field'>";
            }
        }

        /**
         * End Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public 
         */
        public function show_field_end($field, $meta = NULL, $group = false) {
            if (isset($field['desc']) && $field['desc'] != '') {
                echo "<div class='ht--meta-box-desc'>" . wp_kses_post($field['desc']) . "</div>";
            }
            echo "</div>";
            if ($this->inGroup === true) {
                echo "</div>";
                echo "</td>";
            }
        }

        /**
         * Show Field Text.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_text($field, $meta) {
            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}'" : '';
            echo "<input type='text' class='ht--text" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "' id='" . esc_attr($field['id']) . "' value='{$meta}' size='30' " . wp_strip_all_tags($style) . "/>";
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Field number.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_number($field, $meta) {
            $this->show_field_begin($field, $meta);
            $step = (isset($field['step']) || $field['step'] != '1') ? "step='" . $field['step'] . "' " : '';
            $min = isset($field['min']) ? "min='" . $field['min'] . "' " : '';
            $max = isset($field['max']) ? "max='" . $field['max'] . "' " : '';
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}'" : '';
            echo "<input type='number' class='ht--number" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "' id='" . esc_attr($field['id']) . "' value='{$meta}' size='30' " . $step . $min . $max . wp_strip_all_tags($style) . "/>";
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Field hidden.
         *
         * @param string $field 
         * @param string|mixed $meta 
         * @access public
         */
        public function show_field_hidden($field, $meta) {
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';
            echo "<input type='hidden' " . wp_strip_all_tags($style) . "class='ht--text" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "' id='" . esc_attr($field['id']) . "' value='{$meta}'/>";
        }

        /**
         * Show Field Paragraph.
         *
         * @param string $field 
         * @access public
         */
        public function show_field_paragraph($field) {
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) ? " style='{$field['style']}' " : '';
            echo '<p class="ht--paragraph' . esc_attr($class) . '"' . wp_strip_all_tags($style) . '>' . $field['value'] . '</p>';
        }

        /**
         * Show Field Textarea.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_textarea($field, $meta) {
            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';
            echo "<textarea class='ht--textarea large-text" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "' id='" . esc_attr($field['id']) . "' " . wp_strip_all_tags($style) . " cols='60' rows='10'>{$meta}</textarea>";
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Field Select.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_select($field, $meta) {
            if (!is_array($meta))
                $meta = (array) $meta;

            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';

            echo "<select " . wp_strip_all_tags($style) . " class='ht--select" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "" . ( $field['multiple'] ? "[]' id='" . esc_attr($field['id']) . "' multiple='multiple'" : "'" ) . ">";
            foreach ($field['options'] as $key => $value) {
                echo "<option value='{$key}'" . selected(in_array($key, $meta), true, false) . ">{$value}</option>";
            }
            echo "</select>";
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Field Select.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_widget_list($field, $meta) {
            if (!is_array($meta))
                $meta = (array) $meta;

            global $wp_registered_sidebars;
            $this->show_field_begin($field, $meta);
            if ($wp_registered_sidebars) {
                $class = isset($field['class']) ? ' ' . $field['class'] : "";
                $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';
                echo "<select " . wp_strip_all_tags($style) . " class='ht--widget-select" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "" . ( $field['multiple'] ? "[]' id='" . esc_attr($field['id']) . "' multiple='multiple'" : "'" ) . ">";
                echo "<option value='none'" . selected(in_array('none', $meta), true, false) . ">" . esc_html__('-- Choose Widget --', 'hashthemes') . "</option>";
                foreach ($wp_registered_sidebars as $sidebar) {
                    echo "<option value='{$sidebar['id']}'" . selected(in_array($sidebar['id'], $meta), true, false) . ">{$sidebar['name']}</option>";
                }
                echo "</select>";
            }
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Radio Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public 
         */
        public function show_field_radio($field, $meta) {

            if (!is_array($meta))
                $meta = (array) $meta;
            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';

            foreach ($field['options'] as $key => $value) {
                echo "<label><input type='radio' " . wp_strip_all_tags($style) . " class='ht--radio" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "' value='{$key}'" . checked(in_array($key, $meta), true, false) . " /> <span class='ht--radio-label'>{$value}</span></label>";
            }
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Image Radio Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public 
         */
        public function show_field_image_radio($field, $meta) {
            if (!is_array($meta))
                $meta = (array) $meta;

            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';
            foreach ($field['options'] as $key => $value) {
                echo "<label class='ht--meta-box-image-select" . esc_attr($class) . "'>";
                echo "<img src='{$value}'>";
                echo "<input type='radio' " . wp_strip_all_tags($style) . " class='ht--radio' name='" . esc_attr($field['id']) . "' value='{$key}'" . checked(in_array($key, $meta), true, false) . " />";
                echo "<span></span>";
                echo "</label>";
            }
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Checkbox Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_checkbox($field, $meta) {
            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';
            echo "<div class='ht--meta-box-toggle'>";
            echo "<input type='checkbox' " . wp_strip_all_tags($style) . " class='ht--meta-box-toggle-checkbox" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "' id='" . esc_attr($field['id']) . "'" . checked(!empty($meta), true, false) . " />";
            echo "<label class='ht--meta-box-toggle-label' for='" . esc_attr($field['id']) . "'></label>";
            echo "</div>";
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Wysiwig Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_wysiwyg($field, $meta, $in_repeater = false) {
            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";

            if ($in_repeater)
                echo "<textarea class='ht--wysiwyg theEditor large-text" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "' id='" . esc_attr($field['id']) . "' cols='60' rows='10'>{$meta}</textarea>";
            else {
                $settings = ( isset($field['settings']) && is_array($field['settings']) ? $field['settings'] : array() );
                $settings['editor_class'] = 'ht--wysiwyg' . esc_attr($class);
                $id = str_replace("_", "", $this->stripNumeric(strtolower($field['id'])));
                wp_editor(html_entity_decode($meta), $id, $settings);
            }
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Image Field.
         *
         * @param array $field 
         * @param array $meta 
         * @access public
         */
        public function show_field_background($field, $meta) {
            wp_enqueue_media();
            $this->show_field_begin($field, $meta);

            $std = isset($field['std']) ? $field['std'] : array('id' => '', 'url' => '', 'repeat' => 'no-repeat', 'size' => 'auto', 'position' => 'center center', 'attachment' => 'scroll', 'color' => '', 'overlay' => '');
            $name = esc_attr($field['id']);
            $value = wp_parse_args($meta, $std);

            $has_image = empty($value['url']) ? false : true;
            $style = "style='" . ( (!$has_image) ? "display: none;'" : "'");

            echo '<div class="ht--meta-box-bg-param-color">';
            echo "<input class='ht--color-iris' type='text' name='{$name}[color]' value='{$value['color']}' size='8' />";
            echo '</div>';

            echo "<div class='ht--meta-box-image-preview'>";
            if ($has_image) {
                echo "<img src='{$value['url']}'>";
            }
            echo "</div>";

            echo '<div class="ht--meta-box-bg-params" ' . $style . '>';
            echo '<div class="ht--meta-box-bg-param-row">';
            echo '<div class="ht--meta-box-bg-param-col">';
            echo '<label>' . esc_html__('Background Repeat', 'hashthemes') . '</label>';
            echo "<select class='ht--meta-box-bg-repeat' name='{$name}[repeat]'>";
            echo '<option value="no-repeat" ' . selected('no-repeat', $value['repeat'], false) . '>' . esc_html__('No Repeat', 'hashthemes') . '</option>';
            echo '<option value="repeat" ' . selected('repeat', $value['repeat'], false) . '>' . esc_html__('Tile', 'hashthemes') . '</option>';
            echo '<option value="repeat-x" ' . selected('repeat-x', $value['repeat'], false) . '>' . esc_html__('Tile Horizontally', 'hashthemes') . '</option>';
            echo '<option value="repeat-y" ' . selected('repeat-y', $value['repeat'], false) . '>' . esc_html__('Tile Vertically', 'hashthemes') . '</option>';
            echo '</select>';
            echo '</div>';

            echo '<div class="ht--meta-box-bg-param-col">';
            echo '<label>' . esc_html__('Background Size', 'hashthemes') . '</label>';
            echo "<select class='ht--meta-box-bg-size' name='{$name}[size]'>";
            echo '<option value="auto" ' . selected('no-repeat', $value['size'], false) . '>' . esc_html__('Auto', 'hashthemes') . '</option>';
            echo '<option value="cover" ' . selected('repeat', $value['size'], false) . '>' . esc_html__('Cover', 'hashthemes') . '</option>';
            echo '<option value="contain" ' . selected('repeat-x', $value['size'], false) . '>' . esc_html__('Contain', 'hashthemes') . '</option>';
            echo '</select>';
            echo '</div>';

            echo '<div class="ht--meta-box-bg-param-col">';
            echo '<label>' . esc_html__('Background Position', 'hashthemes') . '</label>';
            echo "<select class='ht--meta-box-bg-position' name='{$name}[position]'>";
            echo '<option value="left top" ' . selected('left top', $value['position'], false) . '>' . esc_html__('Left Top', 'hashthemes') . '</option>';
            echo '<option value="left center" ' . selected('left center', $value['position'], false) . '>' . esc_html__('Left Center', 'hashthemes') . '</option>';
            echo '<option value="left bottom" ' . selected('left bottom', $value['position'], false) . '>' . esc_html__('Left Bottom', 'hashthemes') . '</option>';
            echo '<option value="right top" ' . selected('right top', $value['position'], false) . '>' . esc_html__('Right Top', 'hashthemes') . '</option>';
            echo '<option value="right center" ' . selected('right center', $value['position'], false) . '>' . esc_html__('Right Center', 'hashthemes') . '</option>';
            echo '<option value="right bottom" ' . selected('right bottom', $value['position'], false) . '>' . esc_html__('Right Bottom', 'hashthemes') . '</option>';
            echo '<option value="center top" ' . selected('center top', $value['position'], false) . '>' . esc_html__('Center Top', 'hashthemes') . '</option>';
            echo '<option value="center center" ' . selected('center center', $value['position'], false) . '>' . esc_html__('Center Center', 'hashthemes') . '</option>';
            echo '<option value="center bottom" ' . selected('center bottom', $value['position'], false) . '>' . esc_html__('Center Bottom', 'hashthemes') . '</option>';
            echo '</select>';
            echo '</div>';

            echo '<div class="ht--meta-box-bg-param-col">';
            echo '<label>' . esc_html__('Background Attachment', 'hashthemes') . '</label>';
            echo "<select class='ht--meta-box-bg-attachment' name='{$name}[attachment]'>";
            echo '<option value="fixed" ' . selected('fixed', $value['attachment'], false) . '>' . esc_html__('Fixed', 'hashthemes') . '</option>';
            echo '<option value="scroll" ' . selected('scroll', $value['attachment'], false) . '>' . esc_html__('Scroll', 'hashthemes') . '</option>';
            echo '</select>';
            echo '</div>';

            echo '</div>';

            echo '<div class="ht--meta-box-bg-param-overlay">';
            echo '<label>' . esc_html__('Overlay Color', 'hashthemes') . '</label>';
            echo "<input data-alpha-enabled='true' class='ht--color-iris ht--meta-alpha-color' type='text' name='{$name}[overlay]' value='{$value['overlay']}' />";
            echo '</div>';

            echo '</div>';

            echo "<input class='ht--meta-box-image-id' type='hidden' name='{$name}[id]' value='{$value['id']}'/>";
            echo "<input class='ht--meta-box-image-url' type='hidden' name='{$name}[url]' value='{$value['url']}'/>";

            if ($has_image) {
                echo "<input class='button ht--meta-box-remove-image' value='" . esc_html__('Remove Image', 'hashthemes') . "' type='button'/>";
            } else {
                echo "<input class='button ht--meta-box-upload-image' value='" . esc_html__('Upload Image', 'hashthemes') . "' type='button'/>";
            }
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Image Field.
         *
         * @param array $field 
         * @param array $meta 
         * @access public
         */
        public function show_field_image($field, $meta) {
            wp_enqueue_media();
            $this->show_field_begin($field, $meta);

            $std = isset($field['std']) ? $field['std'] : array('id' => '', 'url' => '');
            $name = esc_attr($field['id']);
            $value = isset($meta['id']) ? $meta : $std;

            $value['url'] = isset($meta['src']) ? $meta['src'] : $value['url'];
            $value['repeat'] = isset($meta['repeat']) ? $meta['repeat'] : 'no-repeat';

            $has_image = empty($value['url']) ? false : true;
            $style = "style='" . ( (!$has_image) ? "display: none;'" : "'");

            echo "<div class='ht--meta-box-image-preview'>";
            if ($has_image) {
                echo "<img src='{$value['url']}'>";
            }
            echo "</div>";

            echo "<input class='ht--meta-box-image-id' type='hidden' name='{$name}[id]' value='{$value['id']}'/>";
            echo "<input class='ht--meta-box-image-url' type='hidden' name='{$name}[url]' value='{$value['url']}'/>";

            if ($has_image) {
                echo "<input class='button ht--meta-box-remove-image' value='" . esc_html__('Remove Image', 'hashthemes') . "' type='button'/>";
            } else {
                echo "<input class='button ht--meta-box-upload-image' value='" . esc_html__('Upload Image', 'hashthemes') . "' type='button'/>";
            }
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Gallery Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_gallery($field, $meta) {
            $this->show_field_begin($field, $meta);
            echo '<ul class="ht--meta-box-gallery-container">';
            if ($meta) {
                $images = explode(',', $meta);
                foreach ($images as $image) {
                    $image_src = wp_get_attachment_image_src($image, 'thumbnail');
                    echo '<li data-id="' . $image . '"><span style="background-image:url(' . $image_src[0] . ')"></span><a href="#" class="ht--meta-box-gallery-remove">×</a></li>';
                }
            }
            echo '</ul>';
            echo '<input type="hidden" name="' . $field['id'] . '" value="' . $meta . '" /><a href="#" class="button ht--meta-box-gallery-button">' . esc_html__('Add Images', 'hashthemes') . '</a>';
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Color Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_color($field, $meta) {
            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            echo "<input class='ht--color-iris" . esc_attr($class) . "' type='text' name='" . esc_attr($field['id']) . "' id='" . esc_attr($field['id']) . "' value='{$meta}' size='8' />";

            $this->show_field_end($field, $meta);
        }

        /**
         * Show Alpha Color Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_alpha_color($field, $meta) {
            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            echo "<input data-alpha-enabled='true' class='ht--color-iris" . esc_attr($class) . "' type='text' name='" . esc_attr($field['id']) . "' id='" . esc_attr($field['id']) . "' value='{$meta}' />";

            $this->show_field_end($field, $meta);
        }

        /**
         * Show Checkbox List Field
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_checkbox_list($field, $meta) {
            if (!is_array($meta))
                $meta = (array) $meta;

            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';

            foreach ($field['options'] as $key => $value) {
                echo "<div class='ht--meta-box-checkbox-fields'>";
                echo "<label><input type='checkbox' " . wp_strip_all_tags($style) . "  class='ht--checkbox_list" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "[]' value='{$key}'" . checked(in_array($key, $meta), true, false) . " /> {$value}</label>";
                echo "</div>";
            }

            $this->show_field_end($field, $meta);
        }

        /**
         * Show Date Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_date($field, $meta) {
            $this->show_field_begin($field, $meta);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';
            echo "<input type='text'  " . wp_strip_all_tags($style) . " class='ht--date" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "' id='" . esc_attr($field['id']) . "' rel='{$field['format']}' value='{$meta}' size='30' />";
            $this->show_field_end($field, $meta);
        }

        /**
         * Show time field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public 
         */
        public function show_field_time($field, $meta) {
            $this->show_field_begin($field, $meta);
            $ampm = ($field['ampm']) ? 'true' : 'false';
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';
            echo "<input type='text'  " . wp_strip_all_tags($style) . " class='ht--time" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "' id='" . esc_attr($field['id']) . "' data-ampm='{$ampm}' rel='{$field['format']}' value='{$meta}' size='30' />";
            $this->show_field_end($field, $meta);
        }

        /**
         * Show Posts field.
         * used creating a posts/pages/custom types checkboxlist or a select dropdown
         * @param string $field 
         * @param string $meta 
         * @access public 
         */
        public function show_field_posts($field, $meta) {
            global $post;

            if (!is_array($meta))
                $meta = (array) $meta;
            $this->show_field_begin($field, $meta);
            $options = $field['options'];
            $posts = get_posts($options['args']);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';
            // checkbox_list
            if ('checkbox_list' == $options['type']) {
                foreach ($posts as $p) {
                    echo "<input type='checkbox' " . wp_strip_all_tags($style) . " class='ht--posts-checkbox" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "[]' value='$p->ID'" . checked(in_array($p->ID, $meta), true, false) . " /> $p->post_title<br/>";
                }
            }
            // select
            else {
                echo "<select " . wp_strip_all_tags($style) . " class='ht--posts-select" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "" . ($field['multiple'] ? "[]' multiple='multiple' style='height:auto'" : "'") . ">";
                if (isset($field['emptylabel']))
                    echo '<option value="-1">' . (isset($field['emptylabel']) ? $field['emptylabel'] : __('Select ...', 'mmb')) . '</option>';
                foreach ($posts as $p) {
                    echo "<option value='" . esc_attr($p->ID) . "'" . selected(in_array($p->ID, $meta), true, false) . ">" . esc_html($p->post_title) . "</option>";
                }
                echo "</select>";
            }

            $this->show_field_end($field, $meta);
        }

        /**
         * Show Taxonomy field.
         * used creating a category/tags/custom taxonomy checkboxlist or a select dropdown
         * @param string $field 
         * @param string $meta 
         * @access public 
         * 
         * @uses get_terms()
         */
        public function show_field_taxonomy($field, $meta) {
            global $post;

            if (!is_array($meta))
                $meta = (array) $meta;
            $this->show_field_begin($field, $meta);
            $options = $field['options'];
            $terms = get_terms($options['taxonomy'], $options['args']);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';

            // checkbox_list
            if ('checkbox_list' == $options['type']) {
                foreach ($terms as $term) {
                    echo "<input type='checkbox' " . wp_strip_all_tags($style) . " class='ht--tax-checkbox" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "[]' value='$term->slug'" . checked(in_array($term->slug, $meta), true, false) . " /> " . esc_html($term->name) . "<br/>";
                }
            }
            // select
            else {
                echo "<select " . wp_strip_all_tags($style) . " class='ht--tax-select" . esc_attr($class) . "' name='" . esc_attr($field['id']) . "" . ($field['multiple'] ? "[]' multiple='multiple' style='height:auto'" : "'") . ">";
                foreach ($terms as $term) {
                    echo "<option value='$term->slug'" . selected(in_array($term->slug, $meta), true, false) . "> " . esc_html($term->name) . "</option>";
                }
                echo "</select>";
            }

            $this->show_field_end($field, $meta);
        }

        /**
         * Show conditinal Checkbox Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_cond($field, $meta) {

            $this->show_field_begin($field, $meta);
            $checked = false;
            if (is_array($meta) && isset($meta['enabled']) && $meta['enabled'] == 'on') {
                $checked = true;
            }
            echo '<div class="ht--meta-box-toggle">';
            echo "<input type='checkbox' class='ht--meta-box-conditional-control ht--meta-box-toggle-checkbox' name='" . esc_attr($field['id']) . "[enabled]' id='" . esc_attr($field['id']) . "'" . checked($checked, true, false) . " />";
            echo "<label class='ht--meta-box-toggle-label' for='" . esc_attr($field['id']) . "'></label>";
            echo "</div>";
            $this->show_field_end($field, $meta);
            echo '</div>';

            //start showing the fields
            $display = ($checked) ? '' : ' style="display: none;"';

            echo '<div class="ht--meta-box-conditional-container"' . $display . '>';
            if ($field['inline']) {
                echo '<table class="ht--meta-box-form-table">';
                echo '<tr>';
            }
            foreach ((array) $field['fields'] as $f) {
                if ($field['inline']) {
                    echo '<td>';
                }
                //reset var $id for cond
                $id = '';
                $id = $field['id'] . '[' . $f['id'] . ']';
                $m = '';
                $m = (isset($meta[$f['id']])) ? $meta[$f['id']] : '';
                $m = ( $m !== '' ) ? $m : (isset($f['std']) ? $f['std'] : '');
                if ('image' != $f['type'] && $f['type'] != 'repeater')
                    $m = is_array($m) ? array_map('esc_attr', $m) : esc_attr($m);
                //set new id for field in array format
                $f['id'] = $id;
                $meta_box_class = isset($f['label_block']) && $f['label_block'] ? ' ht--meta-box-label-block' : '';
                echo '<div class="ht--meta-box-row ht--meta-box-' . $f['type'] . $meta_box_class . '">';
                call_user_func(array($this, 'show_field_' . $f['type']), $f, $m);
                echo '</div>';
                if ($field['inline']) {
                    echo '</td>';
                }
            }
            if ($field['inline']) {
                echo '</tr>';
                echo '</table>';
            }
        }

        /**
         * Show Dimension Field.
         *
         * @param string $field 
         * @param string $meta 
         * @access public
         */
        public function show_field_dimension($field, $meta) {
            $this->show_field_begin($field, $meta);
            $std = isset($field['std']) ? $field['std'] : array('left' => '', 'top' => '', 'right' => '', 'bottom' => '');
            $position = isset($field['position']) ? $field['position'] : array('left', 'top', 'right', 'bottom');
            $name = esc_attr($field['id']);
            $value = wp_parse_args($meta, $std);
            $class = isset($field['class']) ? ' ' . $field['class'] : "";
            $style = isset($field['style']) && !empty(trim($field['style'])) ? "style='{$field['style']}' " : '';

            echo "<ul " . wp_strip_all_tags($style) . " class='ht--dimension" . esc_attr($class) . "' id='" . esc_attr($field['id']) . "'>";
            if (in_array('left', $position)) {
                echo '<li class="ht--dimension-wrap">';
                echo '<input type="number" class="ht--dimension-left" name="' . $name . '[left] " value="' . $value['left'] . '" />';
                echo '<span class="ht--dimension-label">' . esc_html__('Left', 'hashthemes') . '</span>';
                echo '</li>';
            }

            if (in_array('top', $position)) {
                echo '<li class="ht--dimension-wrap">';
                echo '<input type="number" class="ht--dimension-top" name="' . $name . '[top]" value="' . $value['top'] . '" />';
                echo '<span class="ht--dimension-label">' . esc_html__('Top', 'hashthemes') . '</span>';
                echo '</li>';
            }

            if (in_array('bottom', $position)) {
                echo '<li class="ht--dimension-wrap">';
                echo '<input type="number" class="ht--dimension-bottom" name="' . $name . '[bottom]" value="' . $value['bottom'] . '" />';
                echo '<span class="ht--dimension-label">' . esc_html__('Bottom', 'hashthemes') . '</span>';
                echo '</li>';
            }

            if (in_array('right', $position)) {
                echo '<li class="ht--dimension-wrap">';
                echo '<input type="number" class="ht--dimension-right" name="' . $name . '[right]" value="' . $value['right'] . '" />';
                echo '<span class="ht--dimension-label">' . esc_html__('Right', 'hashthemes') . '</span>';
                echo '</li>';
            }

            echo '<li class="ht--dimension-wrap">';
            echo '<div class="ht--link-dimensions">';
            echo '<span class="dashicons dashicons-admin-links ht--linked" title="' . esc_html__('Link', 'hashthemes') . '"></span>';
            echo '<span class="dashicons dashicons-editor-unlink ht--unlinked" title="' . esc_html__('Unlink', 'hashthemes') . '"></span>';
            echo '</div>';
            echo '</li>';
            echo '</ul>';
            $this->show_field_end($field, $meta);
        }

        /**
         * Save Data from Metabox
         *
         * @param string $post_id 
         * @access public 
         */
        public function save($post_id) {

            global $post_type;
            $id = $this->_meta_box['id'];

            $post_type_object = get_post_type_object($post_type);

            if (( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )                      // Check Autosave
                    || (!isset($_POST['post_ID']) || $post_id != $_POST['post_ID'] )        // Check Revision
                    || (!in_array($post_type, $this->_meta_box['pages']) )                  // Check if current post type is supported.
                    || (!check_admin_referer(basename(__FILE__), $id . '_meta_box_nonce') )    // Check nonce - Security
                    || (!current_user_can($post_type_object->cap->edit_post, $post_id) )) {  // Check permission
                return $post_id;
            }

            foreach ($this->_fields as $field) {
                $type = $field['type'];

                if (!in_array($type, array('tabopen', 'tabclose'))) {
                    $name = $field['id'];
                    $old = get_post_meta($post_id, $name, !$field['multiple']);
                    $new = ( isset($_POST[$name]) ) ? $_POST[$name] : ( ( $field['multiple'] ) ? array() : '' );

                    //skip on Paragraph field
                    if ($type != "paragraph" && $type != "tabopen" && $type != "tab_close") {

                        // Call defined method to save meta value, if there's no methods, call common one.
                        $save_func = 'save_field_' . $type;
                        if (method_exists($this, $save_func)) {
                            call_user_func(array($this, 'save_field_' . $type), $post_id, $field, $old, $new);
                        } else {
                            $this->save_field($post_id, $field, $old, $new);
                        }
                    }
                }
            } // End foreach
        }

        /**
         * Common function for saving fields.
         *
         * @param string $post_id 
         * @param string $field 
         * @param string $old 
         * @param string|mixed $new 
         * @access public
         */
        public function save_field($post_id, $field, $old, $new) {
            $name = $field['id'];
            delete_post_meta($post_id, $name);
            if ($new === '' || $new === array())
                return;
            if ($field['multiple']) {
                foreach ($new as $add_new) {
                    add_post_meta($post_id, $name, $add_new, false);
                }
            } else {
                update_post_meta($post_id, $name, $new);
            }
        }

        /**
         * function for saving image field.
         *
         * @param string $post_id 
         * @param string $field 
         * @param string $old 
         * @param string|mixed $new 
         * @access public
         */
        public function save_field_image($post_id, $field, $old, $new) {
            $name = $field['id'];
            delete_post_meta($post_id, $name);
            if ($new === '' || $new === array() || $new['id'] == '' || $new['url'] == '')
                return;

            update_post_meta($post_id, $name, $new);
        }

        /**
         * function for saving image field.
         *
         * @param string $post_id 
         * @param string $field 
         * @param string $old 
         * @param string|mixed $new 
         * @access public
         */
        public function save_field_background($post_id, $field, $old, $new) {
            $name = $field['id'];
            delete_post_meta($post_id, $name);
            if ($new === '' || $new === array())
                return;

            update_post_meta($post_id, $name, $new);
        }

        /*
         * Save Wysiwyg Field.
         *
         * @param string $post_id 
         * @param string $field 
         * @param string $old 
         * @param string $new 
         * @access public 
         */

        public function save_field_wysiwyg($post_id, $field, $old, $new) {
            $id = str_replace("_", "", $this->stripNumeric(strtolower($field['id'])));
            $new = ( isset($_POST[$id]) ) ? $_POST[$id] : ( ( $field['multiple'] ) ? array() : '' );
            $this->save_field($post_id, $field, $old, $new);
        }

        /**
         * Save repeater Fields.
         *
         * @param string $post_id 
         * @param string $field 
         * @param string|mixed $old 
         * @param string|mixed $new 
         * @access public 
         */
        public function save_field_repeater($post_id, $field, $old, $new) {
            if (is_array($new) && count($new) > 0) {
                foreach ($new as $n) {
                    foreach ($field['fields'] as $f) {
                        $type = $f['type'];
                        switch ($type) {
                            case 'wysiwyg':
                                $n[$f['id']] = wpautop($n[$f['id']]);
                                break;
                            default:
                                break;
                        }
                    }
                    if (!$this->is_array_empty($n))
                        $temp[] = $n;
                }
                if (isset($temp) && count($temp) > 0 && !$this->is_array_empty($temp)) {
                    update_post_meta($post_id, $field['id'], $temp);
                } else {
                    //  remove old meta if exists
                    delete_post_meta($post_id, $field['id']);
                }
            } else {
                //  remove old meta if exists
                delete_post_meta($post_id, $field['id']);
            }
        }

        /**
         * Save repeater File Field.
         * @param string $post_id 
         * @param string $field 
         * @param string $old 
         * @param string $new 
         * @access public
         * @deprecated 3.0.7
         */
        public function save_field_file_repeater($post_id, $field, $old, $new) {
            
        }

        /**
         * Add missed values for meta box.
         *
         * @access public
         */
        public function add_missed_values() {

            // Default values for meta box
            $this->_meta_box = array_merge(array(
                'context' => 'normal',
                'priority' => 'high',
                'pages' => array('post')
                    ), (array) $this->_meta_box
            );

            // Default values for fields
            foreach ($this->_fields as &$field) {

                $multiple = in_array($field['type'], array('checkbox_list', 'image'));
                $std = $multiple ? array() : '';
                $format = 'date' == $field['type'] ? 'yy-mm-dd' : ( 'time' == $field['type'] ? 'hh:mm' : '' );

                $field = array_merge(array(
                    'multiple' => $multiple,
                    'std' => $std,
                    'desc' => '',
                    'format' => $format,
                    'validate_func' => ''
                        ), $field
                );
            } // End foreach
        }

        /**
         * Check if field with $type exists.
         *
         * @param string $type 
         * @access public
         */
        public function has_field($type) {
            //faster search in single dimention array.
            if (count($this->field_types) > 0) {
                return in_array($type, $this->field_types);
            }

            //run once over all fields and store the types in a local array
            $temp = array();
            foreach ($this->_fields as $field) {
                $temp[] = $field['type'];
                if ('repeater' == $field['type'] || 'cond' == $field['type']) {
                    foreach ((array) $field["fields"] as $repeater_field) {
                        $temp[] = $repeater_field["type"];
                    }
                }
            }

            //remove duplicates
            $this->field_types = array_unique($temp);
            //call this function one more time now that we have an array of field types
            return $this->has_field($type);
        }

        /**
         * Check if current page is edit page.
         *
         * @access public
         */
        public function is_edit_page() {
            global $pagenow;
            return in_array($pagenow, array('post.php', 'post-new.php'));
        }

        /**
         *  Add Field to meta box (generic function)
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         */
        public function addField($id, $args) {
            $new_field = array('id' => $id, 'std' => '', 'desc' => '', 'style' => '');
            $new_field = array_merge($new_field, $args);
            $this->_fields[] = $new_field;
        }

        /**
         *  Add Text Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'icon' => // icon class only - default is dashicons-admin-generic dashicons
         */
        public function openTab($id, $args) {
            $new_field = array(
                'type' => 'tabopen',
                'id' => $id,
                'name' => esc_html__('Title', 'hashthemes')
            );
            $new_field = array_merge($new_field, $args);
            $this->_fields[] = $new_field;
        }

        /**
         *  Add Text Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         */
        public function closeTab() {
            $new_field = array(
                'type' => 'tabclose'
            );
            $this->_fields[] = $new_field;
        }

        /**
         *  Add Text Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'style' =>   // custom style for field, string optional
         *    'validate_func' => // validate function, string optional
         *   @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addText($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'text',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'style' => '',
                'name' => esc_html__('Text Field', 'hashthemes')
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Number Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'style' =>   // custom style for field, string optional
         *    'validate_func' => // validate function, string optional
         *   @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addNumber($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'number',
                'id' => $id,
                'std' => '0',
                'desc' => '',
                'style' => '',
                'name' => esc_html__('Number Field', 'hashthemes'),
                'step' => '1',
                'min' => '0'
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Hidden Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'style' =>   // custom style for field, string optional
         *    'validate_func' => // validate function, string optional
         *   @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addHidden($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'hidden',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'style' => '',
                'name' => esc_html__('Hidden Field', 'hashthemes'),
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Paragraph to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $value  paragraph html
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addParagraph($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'paragraph',
                'id' => $id,
                'value' => ''
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Checkbox Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addCheckbox($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'checkbox',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'style' => '',
                'name' => esc_html__('Checkbox Field', 'hashthemes'),
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add CheckboxList Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $options (array)  array of key => value pairs for select options
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
         *  
         *   @return : remember to call: $checkbox_list = get_post_meta(get_the_ID(), 'meta_name', false); 
         *   which means the last param as false to get the values in an array
         */
        public function addCheckboxList($id, $options, $args, $repeater = false) {
            $new_field = array(
                'type' => 'checkbox_list',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'style' => '',
                'name' => esc_html__('Checkbox List Field', 'hashthemes'),
                'options' => $options,
                'multiple' => false
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Textarea Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'style' =>   // custom style for field, string optional
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addTextarea($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'textarea',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'style' => '',
                'name' => esc_html__('Textarea Field', 'hashthemes')
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Select Field to meta box
         *  @access public
         *  @param $id string field id, i.e. the meta key
         *  @param $options (array)  array of key => value pairs for select options  
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, (array) optional
         *    'multiple' => // select multiple values, optional. Default is false.
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addSelect($id, $options, $args, $repeater = false) {
            $new_field = array(
                'type' => 'select',
                'id' => $id,
                'std' => array(),
                'desc' => '',
                'style' => '',
                'name' => esc_html__('Select Field', 'hashthemes'),
                'multiple' => false,
                'options' => $options
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Select Field to meta box
         *  @access public
         *  @param $id string field id, i.e. the meta key
         *  @param $options (array)  array of key => value pairs for select options  
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, (array) optional
         *    'multiple' => // select multiple values, optional. Default is false.
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addWidgetList($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'widget_list',
                'id' => $id,
                'std' => array(),
                'desc' => '',
                'style' => '',
                'name' => esc_html__('Select Widget', 'hashthemes'),
                'multiple' => false
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Radio Field to meta box
         *  @access public
         *  @param $id string field id, i.e. the meta key
         *  @param $options (array)  array of key => value pairs for radio options
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional 
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
         */
        public function addRadio($id, $options, $args, $repeater = false) {
            $new_field = array(
                'type' => 'radio',
                'id' => $id,
                'std' => array(),
                'desc' => '',
                'name' => esc_html__('Radio Field', 'hashthemes'),
                'options' => $options
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Image Radio Field to meta box
         *  @access public
         *  @param $id string field id, i.e. the meta key
         *  @param $options (array)  array of key => value pairs for radio options
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional 
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
         */
        public function addImageRadio($id, $options, $args, $repeater = false) {
            $new_field = array(
                'type' => 'image_radio',
                'id' => $id,
                'std' => array(),
                'desc' => '',
                'style' => '',
                'name' => esc_html__('Radio Field', 'hashthemes'),
                'options' => $options
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Date Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional
         *    'format' => // date format, default yy-mm-dd. Optional. Default "'d MM, yy'"  See more formats here: http://goo.gl/Wcwxn
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addDate($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'date',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'format' => 'd MM, yy',
                'name' => esc_html__('Date Field', 'hashthemes')
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Time Field to meta box
         *  @access public
         *  @param $id string- field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional
         *    'format' => // time format, default hh:mm. Optional. See more formats here: http://goo.gl/83woX
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addTime($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'time',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'format' => 'hh:mm',
                'name' => esc_html__('Time Field', 'hashthemes'),
                'ampm' => false
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Color Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addColor($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'color',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'name' => esc_html__('ColorPicker Field', 'hashthemes')
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Alpha Color Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addAplhaColor($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'alpha_color',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'name' => esc_html__('ColorPicker Field', 'hashthemes')
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Image Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addImage($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'image',
                'id' => $id,
                'desc' => '',
                'name' => esc_html__('Image Field', 'hashthemes'),
                'std' => array('id' => '', 'url' => ''),
                'multiple' => false
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Gallery Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addGallery($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'gallery',
                'id' => $id, 'desc' => '',
                'name' => esc_html__('Gallery Field', 'hashthemes'),
                'std' => '',
                'multiple' => false
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Background Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addBackground($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'background',
                'id' => $id,
                'desc' => '',
                'name' => esc_html__('Background Field', 'hashthemes'),
                'std' => array(
                    'id' => '',
                    'url' => '',
                    'repeat' => 'no-repeat',
                    'size' => 'auto',
                    'position' => 'center center',
                    'attachment' => 'scroll',
                    'color' => '',
                    'overlay' => ''
                ),
                'multiple' => false
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Background Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'validate_func' => // validate function, string optional
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addDimension($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'dimension',
                'id' => $id,
                'desc' => '',
                'name' => esc_html__('Dimenstion', 'hashthemes'),
                'position' => array('top', 'bottom', 'left', 'right'),
                'std' => array('top' => '', 'bottom' => '', 'left' => '', 'right' => ''),
                'multiple' => false
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add WYSIWYG Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'style' =>   // custom style for field, string optional Default 'width: 300px; height: 400px'
         *    'validate_func' => // validate function, string optional 
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
         */
        public function addWysiwyg($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'wysiwyg',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'style' => 'width: 300px; height: 400px',
                'name' => esc_html__('WYSIWYG Editor Field', 'hashthemes')
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add Taxonomy Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $options mixed|array options of taxonomy field
         *    'taxonomy' =>    // taxonomy name can be category,post_tag or any custom taxonomy default is category
         *    'type' =>  // how to show taxonomy? 'select' (default) or 'checkbox_list'
         *    'args' =>  // arguments to query taxonomy, see http://goo.gl/uAANN default ('hide_empty' => false)  
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional 
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
         */
        public function addTaxonomy($id, $options, $args, $repeater = false) {
            $temp = array(
                'args' => array('hide_empty' => 0),
                'tax' => 'category',
                'type' => 'select'
            );
            $options = array_merge($temp, $options);
            $new_field = array(
                'type' => 'taxonomy',
                'id' => $id,
                'desc' => '',
                'name' => esc_html__('Taxonomy Field', 'hashthemes'),
                'options' => $options
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add posts Field to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $options mixed|array options of taxonomy field
         *    'post_type' =>    // post type name, 'post' (default) 'page' or any custom post type
         *    'type' =>  // how to show posts? 'select' (default) or 'checkbox_list'
         *    'args' =>  // arguments to query posts, see http://goo.gl/is0yK default ('posts_per_page' => -1)  
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional 
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default)
         */
        public function addPosts($id, $options, $args, $repeater = false) {
            $post_type = isset($options['post_type']) ? $options['post_type'] : (isset($args['post_type']) ? $args['post_type'] : 'post');
            $type = isset($options['type']) ? $options['type'] : 'select';
            $q = array(
                'posts_per_page' => -1,
                'post_type' => $post_type
            );
            if (isset($options['args'])) {
                $q = array_merge($q, (array) $options['args']);
            }
            $options = array(
                'post_type' => $post_type,
                'type' => $type,
                'args' => $q
            );
            $new_field = array(
                'type' => 'posts',
                'id' => $id,
                'desc' => '',
                'std' => '',
                'name' => esc_html__('Posts Field', 'hashthemes'),
                'options' => $options,
                'multiple' => false
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         *  Add repeater Field Block to meta box
         *  @access public
         *  @param $id string  field id, i.e. the meta key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'style' =>   // custom style for field, string optional
         *    'validate_func' => // validate function, string optional
         *    'fields' => //fields to repeater  
         */
        public function addRepeaterBlock($id, $args) {
            $new_field = array(
                'type' => 'repeater',
                'id' => $id,
                'name' => esc_html__('Reapeater Field', 'hashthemes'),
                'fields' => array(),
                'inline' => false,
                'sortable' => false
            );
            $new_field = array_merge($new_field, $args);
            $this->_fields[] = $new_field;
        }

        /**
         *  Add Checkbox conditional Field to Page
         *  @access public
         *  @param $id string  field id, i.e. the key
         *  @param $args mixed|array
         *    'name' => // field name/label string optional
         *    'desc' => // field description, string optional
         *    'std' => // default value, string optional
         *    'validate_func' => // validate function, string optional
         *    'fields' => list of fields to show conditionally.
         *  @param $repeater bool  is this a field inside a repeatr? true|false(default) 
         */
        public function addCondition($id, $args, $repeater = false) {
            $new_field = array(
                'type' => 'cond',
                'id' => $id,
                'std' => '',
                'desc' => '',
                'style' => '',
                'inline' => false,
                'name' => esc_html__('Conditional Field', 'hashthemes'),
                'fields' => array()
            );
            $new_field = array_merge($new_field, $args);
            if (false === $repeater) {
                $this->_fields[] = $new_field;
            } else {
                return $new_field;
            }
        }

        /**
         * Finish Declaration of Meta Box
         * @access public
         */
        public function Finish() {
            $this->add_missed_values();
        }

        /**
         * Helper function to check for empty arrays
         * @access public
         * @param $args mixed|array
         */
        public function is_array_empty($array) {
            if (!is_array($array))
                return true;

            foreach ($array as $a) {
                if (is_array($a)) {
                    foreach ($a as $sub_a) {
                        if (!empty($sub_a) && $sub_a != '')
                            return false;
                    }
                }else {
                    if (!empty($a) && $a != '')
                        return false;
                }
            }
            return true;
        }

        /**
         * stripNumeric Strip number form string
         *
         * @access public
         * @param  string $str
         * @return string number less string
         */
        public function stripNumeric($str) {
            return trim(str_replace(range(0, 9), '', $str));
        }

    }

}