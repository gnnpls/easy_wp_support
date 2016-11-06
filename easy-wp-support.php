<?php
/*
Plugin Name: Easy WP Tutorial
Plugin URI: https://www.motivar.io
Description: Give your clients fast and easy support
Version: 0.0
Author: Anastasiou K., Giannopoulos N.
Author URI: https://motivar.io
*/

if (!defined('WPINC')) {
    die;
}

if (is_admin()) {

    /*create the necessary divs*/
    add_action('in_admin_footer', 'easy_wp_support_help');
    function easy_wp_support_help()
    {
        /*$url = site_url();
        print_r($url);*/
        $screen     = get_current_screen();
        /*print_r($screen) ;*/
        $post_typee = $screen->post_type;
        $args       = array(
            'post_type' => 'easy_wp_support_help',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'easy_wp_support_help_posttypes',
                    'value' => serialize(strval($post_typee)),
                    'compare' => 'LIKE'
                )
            )
        );
        $help_posts = get_posts($args);
        if (!empty($help_posts)) {
            echo '
        <div id="pop_up_button">
        <button class="help-button"><a href="#openModal">Help?</a></button>
        <div id="openModal" class="modalDialog">
        <div><a href="#close" title="Close" class="close">X</a>
        <div class="pop_up">';
            foreach ($help_posts as $tutorial) {
                $tut_id = $tutorial->ID;
                echo stripslashes($tutorial->post_content);
            }
            echo '</div></div></div></div>';
        }
    }


    /* on save make the right movements*/
    add_action('acf/save_post', 'easy_wp_support_save_acf', 20);
    function easy_wp_support_save_acf($post_id)
    {
        if ((!wp_is_post_revision($post_id) && 'auto-draft' != get_post_status($post_id) && 'trash' != get_post_status($post_id))) {
            $tt      = get_post_type($post_id);
            $tttile  = isset($_POST['post_title']) ? $_POST['post_title'] : '';
            $changes = $types = array();
            switch ($tt) {
                case 'easy_wp_support_help':
                    // $repeater = $_POST('ctm_help_step');
                    $steps_arrray     = array_values($_POST['acf']);
                    $post_types_array = $steps_arrray[1];
                    $posttypes        = array();
                    foreach ($post_types_array as $parray) {
                        $parray      = array_values($parray);
                        $posttypes[] = $parray[0];
                    }
                    update_post_meta($post_id, 'easy_wp_support_help_posttypes', $posttypes);
                    $steps_array = $steps_arrray[0];
                    $count       = count($steps_array);
                    $msg         = '<h1>' . $ptitle . '</h1>';
                    foreach ($steps_array as $array) {
                        $array      = array_values($array);
                        $step_title = $array[0];
                        $step_img   = $array[1];
                        $img        = get_the_guid($step_img);
                        $step_desc  = $array[2];

                        $msg .= '<h2>' . $step_title . '</h2>';
                        $msg .= '<div class="tutorial_text">' . wpautop($step_desc) . '</div><br />';
                        if (!empty($step_img)) {
                            $msg .= '<p><img src=' . $img . '></img></p>';
                        }
                    }
                    $changes = array(
                        'post_content' => $msg,
                        'post_title' => ucfirst(strtolower($tttile))
                    );
                    $types   = array(
                        '%s',
                        '%s'
                    );
                    break;
                default:
                    $changes['post_name']  = sanitize_title(easy_wp_support_functions_slugify(easy_wp_support_functions_greeklish($tttile)));
                    $changes['post_title'] = ucfirst($tttile);
                    $types                 = array(
                        '%s',
                        '%s'
                    );
                    break;

            }
            /*update post only if the following exist*/
            if ($tt !== 'page') {
                if (!empty($changes) && !empty($types) && count($changes) == count($types)) {
                    easy_wp_support_functions_update_post($post_id, $changes, $types);
                }

            }
        }
    }




    /*load dynamic the scripts*/
    $path = plugin_dir_path(__FILE__) . '/scripts/';
    /*check which dynamic scripts should be loaded*/
    if (file_exists($path)) {
        $paths = array(
            'js',
            'css'
        );
        foreach ($paths as $kk) {
            $check = glob($path . '*.' . $kk);
            if (!empty($check)) {

                foreach (glob($path . '*.' . $kk) as $filename) {
                    switch ($kk) {
                        case 'js':
                            wp_enqueue_script('easy-wp-support-' . basename($filename), plugin_dir_url(__FILE__) . 'scripts/' . basename($filename), array(), array(), true);
                            break;
                        default:
                            wp_enqueue_style('easy-wp-support-' . basename($filename), plugin_dir_url(__FILE__) . 'scripts/' . basename($filename), array(), '', 'all');
                            break;
                    }
                }

            }
        }

    }






}


/* change slug*/
function easy_wp_support_functions_update_post($id, $changes, $types)
{
    /*id, array('post_title'=>$title) */
    global $wpdb;
    $wpdb->update($wpdb->posts, $changes, array(
        'ID' => $id
    ), $types, array(
        '%d'
    ));
}

/*register post type*/
function easy_wp_support_my_custom_posts($post_type)
{
    $all = array(
        array(
            'post' => 'easy_wp_support_post',
            'sn' => 'Tutorial',
            'pl' => 'Tutorials',
            'args' => array(
                'title',
                'editor'
            ),
            'chk' => true,
            'mnp' => 3,
            'icn' => '',
            'slug' => get_option('easy_wp_support_post_slug') ?: 'easy-wp-tutorials',
            'en_slg' => 1
        )
    );
    if ($post_type == 'all') {
        $msg = $all;
    } else {
        foreach ($all as $k) {
            $posttype = $k['post'];
            if ($posttype == $post_type) {
                $msg = $k;
            }
        }
    }
    return $msg;
}

add_action('init', 'easy_wp_support_register_my_cpts');

function easy_wp_support_register_my_cpts()
{
    $names = easy_wp_support_my_custom_posts('all');

    foreach ($names as $n) {
        $chk          = $n['chk'];
        $hierarchical = '';
        if ($chk == 'true') {
            $hierarchical == 'false';
        } else {
            $hierarchical == 'true';
        }
        $labels = $args = array();
        $labels = array(
            'name' => $n['pl'],
            'singular_name' => $n['sn'],
            'menu_name' => '' . $n['pl'],
            'add_new' => 'New ' . $n['sn'],
            'add_new_item' => 'New ' . $n['sn'],
            'edit' => 'Edit',
            'edit_item' => 'Edit ' . $n['sn'],
            'new_item' => 'New ' . $n['sn'],
            'view' => 'View ' . $n['sn'],
            'view_item' => 'View ' . $n['sn'],
            'search_items' => 'Search ' . $n['sn'],
            'not_found' => 'No ' . $n['pl'],
            'not_found_in_trash' => 'No trushed ' . $n['pl'],
            'parent' => 'Parent ' . $n['sn']
        );
        $args   = array(
            'labels' => $labels,
            'description' => 'My Simple Bookings post type for ' . $n['pl'],
            'public' => $n['chk'],
            'show_ui' => true,
            'has_archive' => $n['chk'],
            'show_in_menu' => true,
            'exclude_from_search' => $n['chk'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'hierarchical' => $hierarchical,
            'rewrite' => array(
                'slug' => $n['post'],
                'with_front' => true
            ),
            'query_var' => true,
            'supports' => $n['args']
        );

        if (!empty($n['slug'])) {
            $args['rewrite']['slug'] = $n['slug'];
        }

        if (!empty($n['mnp'])) {
            $args['menu_position'] = $n['mnp'];
        }

        if (!empty($n['icn'])) {
            $args['menu_icon'] = $n['icn'];
        }
        register_post_type($n['post'], $args);

        if (isset($n['en_slg']) && $n['en_slg'] == 1) {
            add_action('load-options-permalink.php', function($views) use ($n)
            {
                if (isset($_POST[$n['post'] . '_slug'])) {
                    update_option($n['post'] . '_slug', sanitize_title_with_dashes($_POST[$n['post'] . '_slug']));
                }

                add_settings_field($n['post'] . '_slug', __($n['pl'] . ' Slug'), function($views) use ($n)
                {
                    $value = get_option($n['post'] . '_slug');
                    echo '<input type="text" value="' . esc_attr($value) . '" name="' . $n['post'] . '_slug' . '" id="' . $n['post'] . '_slug' . '" class="regular-text" placeholder="' . $n['slug'] . '"/>';

                }, 'permalink', 'optional');
            });

        }


    }
}

