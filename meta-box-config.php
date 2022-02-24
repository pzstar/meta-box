<?php

if (!class_exists('HashThemes_Meta_Box_Config')) {

    class HashThemes_Meta_Box_Config {

        /**
         * Constructor
         *
         * @access public
         *
         * @param array $meta_box 
         */
        public function __construct() {
            if (!is_admin()) {
                return;
            }

            require get_template_directory() . '/inc/meta-box/meta-box-class.php';

            $this->add_post_metabox();
        }

        public function add_post_metabox() {
            $config = array(
                'id' => 'hashthemes_metabox', // meta box id, unique per meta box
                'title' => esc_html__('Simple Meta Box Fields', 'hashthemes'), // meta box title
                'pages' => array('post', 'page'), // post types, accept custom post types as well, default is array('post'); optional
                'context' => 'normal', // where the meta box appear: normal (default), advanced, side; optional
                'priority' => 'high', // order of meta box: high (default), low; optional
                'fields' => array(), // list of meta fields (can be added by field arrays)
                'use_with_theme' => true          //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
            );


            /*
             * Initiate your meta box
             */
            $meta = new HashThemes_Meta_Box($config);

            /*
             * Add fields to your meta box
             */
            $meta->openTab('hashthemes_general_setting', array(
                'name' => esc_html__('General Settings', 'hashthemes'),
                'icon' => 'dashicons-admin-generic dashicons'
            ));
            //text field
            $meta->addText('hashthemes_text_field_id', array(
                'name' => esc_html__('My Text', 'hashthemes'),
                'label_block' => true,
            ));
            //textarea field
            $meta->addTextarea('hashthemes_textarea_field_id', array(
                'name' => esc_html__('My Textarea', 'hashthemes'),
                'label_block' => true
            ));
            //number field
            $meta->addNumber('hashthemes_number_field_id', array(
                'name' => esc_html__('My Number', 'hashthemes'),
                'label_block' => true,
                'step' => 2,
                'min' => 2,
                'max' => 10
            ));
            //paragraph field
            $meta->addParagraph('hashthemes_paragraph_field_id', array(
                'value' => esc_html__('This is Paragraph Text', 'hashthemes')
            ));

            $meta->closeTab();

            $meta->openTab('hashthemes_more_setting', array(
                'name' => esc_html__('More Settings', 'hashthemes'),
                'icon' => 'dashicons-admin-generic dashicons'
            ));
            //checkbox field
            $meta->addCheckbox('hashthemes_checkbox_field_id', array(
                'name' => esc_html__('My Checkbox', 'hashthemes'),
                'std' => true
            ));
            //select field
            $meta->addSelect('hashthemes_select_field_id', array(
                'selectkey1' => esc_html__('Select Value 1', 'hashthemes'),
                'selectkey2' => esc_html__('Select Value 2', 'hashthemes')
                    ), array(
                'name' => esc_html__('My Select', 'hashthemes'),
                'std' => array('selectkey2')
            ));
            //radio field
            $meta->addRadio('hashthemes_radio_field_id', array(
                'radiokey1' => esc_html__('Radio Value1', 'hashthemes'),
                'radiokey2' => esc_html__('Radio Value2', 'hashthemes')
                    ), array(
                'name' => esc_html__('My Radio Field', 'hashthemes'),
                'std' => array('radiokey1')
            ));
            //imageradio field
            $meta->addImageRadio('hashthemes_image_radio_field_id', array(
                'radiokey1' => get_template_directory_uri() . '/images/post-page/layout1.png',
                'radiokey2' => get_template_directory_uri() . '/images/post-page/layout2.png'
                    ), array(
                'name' => esc_html__('My Image Radio Field', 'hashthemes'),
                'std' => array('radiokey1')
            ));
            //Image field
            $meta->addImage('hashthemes_image_field_id', array(
                'name' => esc_html__('My Image', 'hashthemes')
            ));

            $meta->closeTab();

            $meta->openTab('hashthemes_conditional_setting', array(
                'name' => esc_html__('Conditional Settings', 'hashthemes'),
                'icon' => 'dashicons-admin-generic dashicons'
            ));

            /*
             * To Create a conditinal Block first create an array of fields
             * use the same functions as above but add true as a last param (like the repater block)
             */
            $Conditinal_fields[] = $meta->addText('hashthemes_cond_text_field_id', array(
                'name' => esc_html__('My Text', 'hashthemes')
                    ), true);
            $Conditinal_fields[] = $meta->addTextarea('hashthemes_cond_textarea_field_id', array(
                'name' => esc_html__('My Textarea', 'hashthemes')
                    ), true);
            $Conditinal_fields[] = $meta->addCheckbox('hashthemes_cond_checkbox_field_id', array(
                'name' => esc_html__('My Checkbox', 'hashthemes')
                    ), true);
            $Conditinal_fields[] = $meta->addColor('hashthemes_cond_color_field_id', array(
                'name' => esc_html__('My color', 'hashthemes')
                    ), true);

            /*
             * Then just add the fields to the repeater block
             */
            //repeater block
            $meta->addCondition('hashthemes_conditinal_fields', array(
                'name' => __('Enable conditinal fields? ', 'hashthemes'),
                'desc' => __('<small>Turn ON if you want to enable the <strong>conditinal fields</strong>.</small>', 'hashthemes'),
                'fields' => $Conditinal_fields,
                'inline' => false,
            ));

            $meta->closeTab();

            /*
             * Don't Forget to Close up the meta box Declaration 
             */

            //Finish Meta Box Declaration 
            $meta->Finish();

            /**
             * Create a second metabox
             */
            /*
             * configure your meta box
             */
            $config2 = array(
                'id' => 'hashthemes_metabox2', // meta box id, unique per meta box
                'title' => esc_html__('Advanced Meta Box fields', 'hashthemes'), // meta box title
                'pages' => array('post', 'page'), // post types, accept custom post types as well, default is array('post'); optional
                'context' => 'normal', // where the meta box appear: normal (default), advanced, side; optional
                'priority' => 'high', // order of meta box: high (default), low; optional
                'fields' => array(), // list of meta fields (can be added by field arrays)
                'use_with_theme' => true          //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
            );


            /*
             * Initiate your 2nd meta box
             */
            $meta2 = new HashThemes_Meta_Box($config2);

            /*
             * Add fields to your 2nd meta box
             */
            //add checkboxes list 
            $meta2->addCheckboxList('hashthemes_checkboxList_field_id', array(
                'checkboxkey1' => esc_html__('Checkbox Value 1', 'hashthemes'),
                'checkboxkey2' => esc_html__('Checkbox Value 2', 'hashthemes')
                    ), array(
                'name' => esc_html__('My checkbox List', 'hashthemes'),
                'std' => array('checkboxkey1'),
            ));
            //date field
            $meta2->addDate('hashthemes_date_field_id', array(
                'name' => esc_html__('My Date', 'hashthemes')
            ));
            //Time field
            $meta2->addTime('hashthemes_time_field_id', array(
                'name' => esc_html__('My Time', 'hashthemes')
            ));
            //Color field
            $meta2->addColor('hashthemes_color_field_id', array(
                'name' => esc_html__('My Color', 'hashthemes'),
                'std' => '#FF0000'
            ));
            //Alpha Color field
            $meta2->addAplhaColor('hashthemes_alpha_color_field_id', array(
                'name' => esc_html__('My Aplha Color', 'hashthemes'),
                'std' => 'rgba(0,0,0,0.5)'
            ));
            //wysiwyg field
            $meta2->addWysiwyg('hashthemes_wysiwyg_field_id', array(
                'name' => esc_html__('My wysiwyg Editor', 'hashthemes')
            ));
            //widget field
            $meta2->addWidgetList('hashthemes_widget_field_id', array(
                'name' => esc_html__('Widget List', 'hashthemes')
            ));
            //taxonomy field
            $meta2->addTaxonomy('hashthemes_taxonomy_field_id', array(
                'taxonomy' => 'category'
                    ), array(
                'name' => esc_html__('My Taxonomy', 'hashthemes')
            ));
            //posts field
            $meta2->addPosts('hashthemes_posts_field_id', array(
                'post_type' => 'post'
                    ), array(
                'name' => esc_html__('My Posts', 'hashthemes')
            ));

            $meta2->addGallery('hashthemes_gallery_field_id', array(
                'name' => esc_html__('Post Gallery Images', 'hashthemes'),
                'desc' => esc_html__('Drag to Reorder the position', 'hashthemes')
            ));

            $meta2->addBackground('hashthemes_background_field_id', array(
                'name' => esc_html__('Background', 'hashthemes')
            ));

            $meta2->addDimension('hashthemes_dimension_field_id', array(
                'name' => esc_html__('Padding', 'hashthemes'),
                'position' => array('top', 'bottom', 'left', 'right'),
            ));

            /*
             * To Create a reapeater Block first create an array of fields
             * use the same functions as above but add true as a last param
             */
            $repeater_fields[] = $meta2->addText('hashthemes_re_text_field_id', array(
                'name' => esc_html__('My Text', 'hashthemes'),
                'label_block' => true
                    ), true);
            $repeater_fields[] = $meta2->addCheckbox('hashthemes_re_checkbox_field_id', array(
                'name' => esc_html__('My Checkbox', 'hashthemes'),
                'label_block' => true
                    ), true);
            $repeater_fields[] = $meta2->addImage('hashthemes_image_field_id', array(
                'name' => esc_html__('My Image', 'hashthemes'),
                'label_block' => true
                    ), true);
            /*
             * Then just add the fields to the repeater block
             */
            //repeater block
            $meta2->addRepeaterBlock('hashthemes_re_', array(
                'inline' => true,
                'name' => esc_html__('This is a Repeater Block', 'hashthemes'),
                'fields' => $repeater_fields,
                'sortable' => true
            ));

            /*
             * To Create a conditinal Block first create an array of fields
             * use the same functions as above but add true as a last param (like the repater block)
             */
            $Conditinal_fields[] = $meta2->addText('hashthemes_con_text_field_id', array(
                'name' => esc_html__('My Text', 'hashthemes')
                    ), true);
            $Conditinal_fields[] = $meta2->addTextarea('hashthemes_con_textarea_field_id', array(
                'name' => esc_html__('My Textarea', 'hashthemes')
                    ), true);
            $Conditinal_fields[] = $meta2->addCheckbox('hashthemes_con_checkbox_field_id', array(
                'name' => esc_html__('My Checkbox', 'hashthemes')
                    ), true);
            $Conditinal_fields[] = $meta2->addColor('hashthemes_con_color_field_id', array(
                'name' => esc_html__('My color', 'hashthemes')
                    ), true);

            /*
             * Then just add the fields to the repeater block
             */
            //repeater block
            $meta2->addCondition('conditinal_fields', array(
                'name' => __('Enable conditinal fields? ', 'hashthemes'),
                'desc' => __('<small>Turn ON if you want to enable the <strong>conditinal fields</strong>.</small>', 'hashthemes'),
                'fields' => $Conditinal_fields,
                'inline' => false,
            ));

            /*
             * Don't Forget to Close up the meta box Declaration 
             */
            //Finish Meta Box Declaration 
            $meta2->Finish();


            $config3 = array(
                'id' => 'hashthemes_metabox3', // meta box id, unique per meta box
                'title' => esc_html__('Groupped Meta Box fields', 'hashthemes'), // meta box title
                'pages' => array('post', 'page'), // post types, accept custom post types as well, default is array('post'); optional
                'context' => 'normal', // where the meta box appear: normal (default), advanced, side; optional
                'priority' => 'low', // order of meta box: high (default), low; optional
                'fields' => array(), // list of meta fields (can be added by field arrays)
                'use_with_theme' => true          //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
            );


            /*
             * Initiate your 3rd meta box
             */
            $meta3 = new HashThemes_Meta_Box($config3);
            //first field of the group has 'group' => 'start' and last field has 'group' => 'end'
            //text field
            $meta3->addText('hashthemes_group_text_field_id', array(
                'name' => esc_html__('My Text', 'hashthemes'),
                'label_block' => true,
                'group' => 'start'
            ));
            //checkbox field
            $meta3->addCheckbox('hashthemes_group_checkbox_field_id', array(
                'name' => esc_html__('My Checkbox', 'hashthemes'),
                'label_block' => true,
            ));
            //select field
            $meta3->addSelect('hashthemes_group_select_field_id', array(
                'selectkey1' => esc_html__('Select Value1', 'hashthemes'),
                'selectkey2' => esc_html__('Select Value2', 'hashthemes')
                    ), array(
                'name' => esc_html__('My select', 'hashthemes'),
                'std' => array('selectkey2'),
                'label_block' => true,
            ));
            //radio field
            $meta3->addRadio('hashthemes_group_radio_field_id', array(
                'radiokey1' => esc_html__('Radio Value1', 'hashthemes'),
                'radiokey2' => esc_html__('Radio Value2', 'hashthemes')
                    ), array(
                'name' => esc_html__('My Radio Field', 'hashthemes'),
                'std' => array('radiokey2'),
                'label_block' => true,
                'group' => 'end'
            ));

            /*
             * Don't Forget to Close up the meta box Declaration 
             */
            //Finish Meta Box Declaration 
            $meta3->Finish();
        }

    }

}

new HashThemes_Meta_Box_Config();
