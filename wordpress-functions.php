<?php
/**
 * Headless WordPress REST API customizations for Blit Creative Studio.
 * Custom Post Type: 'project'
 * Extended fields: Video URL, Category, Year, Client, Tags, Hero Video & Featured Image URL.
 * Paste this snippet into your active WordPress theme's functions.php file.
 */

// 1. Register Custom Post Type: 'project'
add_action('init', 'blit_register_project_post_type');
function blit_register_project_post_type() {
    $labels = [
        'name'               => _x('Projects', 'post type general name', 'blit-studio'),
        'singular_name'      => _x('Project', 'post type singular name', 'blit-studio'),
        'menu_name'          => _x('Projects', 'admin menu', 'blit-studio'),
        'name_admin_bar'     => _x('Project', 'add new on admin bar', 'blit-studio'),
        'add_new'            => _x('Add New', 'project', 'blit-studio'),
        'add_new_item'       => __('Add New Project', 'blit-studio'),
        'new_item'           => __('New Project', 'blit-studio'),
        'edit_item'          => __('Edit Project', 'blit-studio'),
        'view_item'          => __('View Project', 'blit-studio'),
        'all_items'          => __('All Projects', 'blit-studio'),
        'search_items'       => __('Search Projects', 'blit-studio'),
        'parent_item_colon'  => __('Parent Projects:', 'blit-studio'),
        'not_found'          => __('No projects found.', 'blit-studio'),
        'not_found_in_trash' => __('No projects found in Trash.', 'blit-studio')
    ];

    $args = [
        'labels'             => $labels,
        'description'        => __('Creative Portfolio Works for Blit Studio.', 'blit-studio'),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'projects'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-format-video', // Video overlay icon
        'show_in_rest'       => true, // CRITICAL: Makes this endpoint readable via WordPress REST API
        'rest_base'          => 'projects',
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
    ];

    register_post_type('project', $args);
}

// 2. Mock programmatically registered ACF custom field parameters to avoid UI reliance
add_action('acf/init', 'blit_register_acf_portfolio_fields');
function blit_register_acf_portfolio_fields() {
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group(array(
            'key' => 'group_blit_portfolio_fields',
            'title' => 'Project Metadata Scope',
            'fields' => array(
                array(
                    'key' => 'field_project_video_url',
                    'label' => 'Hover Video Preview URL',
                    'name' => 'project_video_url',
                    'type' => 'url',
                    'instructions' => 'Paste dynamic streaming MP4 preview link (for Works hovering panels).',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_project_category',
                    'label' => 'Project Category',
                    'name' => 'project_category',
                    'type' => 'select',
                    'choices' => array(
                        'immersive' => 'Immersive Interactive Space',
                        'scenography' => 'Generative Scenography Projection',
                        'installation' => 'Volumetric Light Installation',
                    ),
                    'default_value' => 'immersive',
                ),
                array(
                    'key' => 'field_project_year',
                    'label' => 'Project Year',
                    'name' => 'project_year',
                    'type' => 'text',
                    'default_value' => '2025',
                ),
                array(
                    'key' => 'field_project_client',
                    'label' => 'Client Name',
                    'name' => 'project_client',
                    'type' => 'text',
                    'default_value' => 'Creative Hub',
                ),
                array(
                    'key' => 'field_project_tags',
                    'label' => 'Tags List',
                    'name' => 'project_tags',
                    'type' => 'text',
                    'instructions' => 'Separate tags with commas, e.g. "ThreeJS, WebGL, Real-time Physics"',
                ),
                array(
                    'key' => 'field_project_hero_video',
                    'label' => 'Hero/Full Video URL',
                    'name' => 'project_hero_video',
                    'type' => 'url',
                    'instructions' => 'Paste the high-quality full resolution streaming MP4 file link for detail viewers.',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'project',
                    ),
                ),
            ),
        ));
    }
}

// 3. Register additional fields with register_rest_field so frontend can retrieve images without dual query lookups
add_action('rest_api_init', 'blit_register_rest_api_extension_fields');
function blit_register_rest_api_extension_fields() {
    // Add Featured Image Full Path directly
    register_rest_field('project', 'featured_image_url', array(
        'get_callback'    => 'blit_get_rest_featured_image_url',
        'update_callback' => null,
        'schema'          => null,
    ));

    // Consolidate metadata packs
    register_rest_field('project', 'acf_fields', array(
        'get_callback'    => 'blit_get_rest_acf_bundled_fields',
        'update_callback' => null,
        'schema'          => null,
    ));
}

function blit_get_rest_featured_image_url($object, $field_name, $request) {
    if ($object['featured_media']) {
        $img = wp_get_attachment_image_src($object['featured_media'], 'full');
        return $img ? $img[0] : false;
    }
    return false;
}

function blit_get_rest_acf_bundled_fields($object, $field_name, $request) {
    $post_id = $object['id'];
    return array(
        'project_video_url' => get_field('project_video_url', $post_id) ?: '',
        'project_category'  => get_field('project_category', $post_id) ?: 'immersive',
        'project_year'      => get_field('project_year', $post_id) ?: '2025',
        'project_client'    => get_field('project_client', $post_id) ?: 'Studio Client',
        'project_tags'      => get_field('project_tags', $post_id) ?: '',
        'project_hero_video'=> get_field('project_hero_video', $post_id) ?: '',
    );
}

// 4. Enable Cross-Origin Resource Sharing (CORS) header limits for front-end access fetch requests
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        header('Access-Control-Allow-Headers: Authorization, X-WP-Nonce, Content-Type, Accent-Control-Allow-Headers');
        return $value;
    }, 15);
}, 15);
