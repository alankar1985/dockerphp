<?php


/*
Plugin Name: AMP modifications
Description: AMP modifications
Version: 1.0
Author: Alankar
*/
add_action( 'pre_get_posts', 'modify_query_for_amp_pages' );

function modify_query_for_amp_pages( $query ) {

    // Check if we are on the main query and it's not in the admin area
    if ( $query->is_main_query() && !is_admin() ) {
        // Include 'book' post type in category archives
        if ( $query->is_category() || $query->is_tag() ) {
            // Get existing post types in the query
            $post_types = $query->get('post_type');
            
            // Check if post types is set, if not, initialize it as an array
            if ( empty($post_types) ) {
                $post_types = array('post');
            }
            
            // Ensure it's an array to merge additional post types
            if ( !is_array($post_types) ) {
                $post_types = array($post_types);
            }
            
            // Add the custom post type 'book' to the query
            $post_types[] = 'blog';
            
            // Set the modified post types back to the query
            $query->set('post_type', $post_types);
        }
    }
}


function my_custom_post_blog() {

	//labels array added inside the function and precedes args array
	
	$labels = array(
	'name' => _x( 'Blog', 'post type general name' ),
	'singular_name' => _x( 'Blog', 'post type singular name' ),
	'add_new' => _x( 'Add New', 'Blog' ),
	'add_new_item' => __( 'Add New Blog' ),
	'edit_item' => __( 'Edit Blog' ),
	'new_item' => __( 'New Blog' ),
	'all_items' => __( 'All Blog' ),
	'view_item' => __( 'View Blog' ),
	'search_items' => __( 'Search Blog' ),
	'not_found' => __( 'No Blog found' ),
	'not_found_in_trash' => __( 'No Blog found in the Trash' ),
	'parent_item_colon' => '',
	'menu_name' => 'Blog'
	);
	
	// args array
	
	$args = array(
	'labels' => $labels,
	'description' => '',
	'public' => true,
	'menu_position' => 4,
	'taxonomies' => array('post_tag','category'),
	'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments','tag' ),
	'has_archive' => true,
    'show_in_rest' => true,
    'rest_base'          => 'blogs',
    'rest_controller_class' => 'WP_REST_Posts_Controller',
	);
	
	register_post_type( 'blog', $args );
}
add_action( 'init', 'my_custom_post_blog' );


function exclude_categories_from_front_page( $query ) {
    if ( $query->is_home() && $query->is_main_query() ) {
        // Get category and subcategory IDs by slugs
        $excluded_categories = array(
            get_term_by( 'slug','panchatantra', 'category' )->term_id,
            // get_term_by( 'slug', 'category-slug-2', 'category' )->term_id
        );
// print_r($excluded_categories);
        // Get all child categories of the excluded categories
        $child_categories = array();
        foreach ( $excluded_categories as $category_id ) {
            $child_categories = array_merge(
                $child_categories,
                get_term_children( $category_id, 'category' )
            );
        }

        // Merge parent and child categories to exclude
        $all_excluded_categories = array_merge( $excluded_categories, $child_categories );

        // Exclude categories from the query
        $query->set( 'category__not_in', $all_excluded_categories );
    }
}
// print('Alankar');
add_action( 'pre_get_posts', 'exclude_categories_from_front_page' );


// Register custom REST API route to fetch menu items
function custom_register_menu_rest_route() {
    register_rest_route('custom/v1', '/menu/(?P<id>[a-zA-Z0-9-_]+)', array(
        'methods'  => 'GET',
        'callback' => 'custom_get_menu_items',
        'permission_callback' => 'custom_basic_auth_permission_check', // No permission checks
    ));
}
add_action('rest_api_init', 'custom_register_menu_rest_route');

// Function to get menu items based on menu slug or ID
function custom_get_menu_items($data) {
    $menu_id = $data['id'];
    $locations = get_nav_menu_locations();
    
    if (isset($locations[$menu_id])) {
        $menu = wp_get_nav_menu_object($locations[$menu_id]);
    } else {
        $menu = wp_get_nav_menu_object($menu_id);
    }

    if (!$menu) {
        return new WP_Error('no_menu', 'Menu not found', array('status' => 404));
    }

    $menu_items = wp_get_nav_menu_items($menu->term_id);
    
    return rest_ensure_response($menu_items);
}


function custom_register_rank_math_meta() {
    // Register Rank Math SEO title


    register_meta('post', 'rank_math_title', array(
        'type'         => 'string',
        'description'  => 'Rank Math SEO Title',
        'single'       => true,
        'show_in_rest' => true,
    ));
    
    // Register Rank Math SEO description
    register_meta('post', 'rank_math_description', array(
        'type'         => 'string',
        'description'  => 'Rank Math SEO Description',
        'single'       => true,
        'show_in_rest' => true,
    ));
    
    // Register Rank Math focus keyword
    register_meta('post', 'rank_math_focus_keyword', array(
        'type'         => 'string',
        'description'  => 'Rank Math Focus Keyword',
        'single'       => true,
        'show_in_rest' => true,
    ));
    
}
add_action('init', 'custom_register_rank_math_meta');




function add_rank_math_meta_to_rest_api() {
    // Check if Rank Math SEO is active
    if (class_exists('RankMath')) {

        // Register custom fields for the REST API
        register_rest_field('blog', 'meta', array(
            'get_callback' => function ($post) {
                // Fetch Rank Math SEO meta data
                $seo_meta = array(
                    'rank_math_title' => get_post_meta($post['id'], 'rank_math_title', true),
                    'rank_math_description' => get_post_meta($post['id'], 'rank_math_description', true),
                    'rank_math_focus_keyword' => get_post_meta($post['id'], 'rank_math_focus_keyword', true),
                );

                return $seo_meta;
            },
            'schema' => array(
                'description' => __('Rank Math SEO Meta Data'),
                'type'        => 'object',
            ),
        ));
    }
}

add_action('rest_api_init', 'add_rank_math_meta_to_rest_api');

function add_cors_http_header(){

    header("Access-Control-Allow-Origin: *");
    }
    add_action('init','add_cors_http_header');


    // Register the custom Web Stories API route
    function custom_register_web_stories_route() {
        register_rest_route( 'custom/v1', '/web-stories', array(
            'methods'  => 'GET',
            'callback' => 'get_web_stories',
            'permission_callback' => '__return_true', // Modify if you want to restrict access
        ));
    }
    
    // Fetch Web Stories with images, text content, slug, and other details
    function get_web_stories( WP_REST_Request $request ) {
        // Define arguments to fetch Web Stories
        $args = array(
            'post_type'      => 'web-story', // Post type for Web Stories
            'posts_per_page' => -1,          // Fetch all stories, you can adjust this for pagination
        );
    
        $stories = new WP_Query( $args );
    
        // Check if we have posts
        if ( !$stories->have_posts() ) {
            return new WP_REST_Response( array(), 200 );
        }
    
        $response = array();
    
        // Loop through the posts
        while ( $stories->have_posts() ) {
            $stories->the_post();
    
            // Get all story content (text, images, etc.)
            $story_content = apply_filters( 'the_content', get_the_content() );
    
            // Extract story metadata (slug, date, link)
            $story_data = array(
                'id'       => get_the_ID(),
                'title'    => get_the_title(),
                'slug'     => get_post_field( 'post_name', get_the_ID() ), // Slug
                'link'     => get_permalink(),
                'date'     => get_the_date(),
                'content'  => $story_content, // Full content including text and images
                'images'   => array(),        // Placeholder for image data
            );
    
            // Extract all images from content
            if ( has_post_thumbnail() ) {
                // Get the featured image
                $featured_image_id = get_post_thumbnail_id( get_the_ID() );
                $image_data = get_image_data( $featured_image_id );
                if ( $image_data ) {
                    $story_data['images'][] = $image_data; // Add featured image
                }
            }
    
            // Extract any other images inside the content
            preg_match_all( '/<img[^>]+>/i', $story_content, $image_tags );
            foreach ( $image_tags[0] as $image_tag ) {
                $image_data = get_image_data_from_tag( $image_tag );
                if ( $image_data ) {
                    $story_data['images'][] = $image_data; // Add each image
                }
            }
    
            $response[] = $story_data; // Add the story data to the response
        }
    
        wp_reset_postdata();
    
        return new WP_REST_Response( $response, 200 );
    }
    
    // Function to extract image data by ID
    function get_image_data( $image_id ) {
        $image_url = wp_get_attachment_image_src( $image_id, 'full' );
        $alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
    
        if ( $image_url ) {
            return array(
                'url'       => $image_url[0],
                'alt'       => $alt_text ? $alt_text : '',  // Fallback if no alt text
                'type'      => wp_check_filetype( $image_url[0] )['ext'], // Image type (e.g., jpg, png)
            );
        }
    
        return null;
    }
    
    // Function to extract image data from an image tag
    function get_image_data_from_tag( $image_tag ) {
        // Use DOMDocument to parse the image tag
        $dom = new DOMDocument();
        @$dom->loadHTML( $image_tag ); // Suppress warnings with @
        $img = $dom->getElementsByTagName('img')->item(0);
    
        if ( $img ) {
            $image_src = $img->getAttribute('src');
            $alt_text  = $img->getAttribute('alt');
    
            // Get the image type from its src
            $image_type = wp_check_filetype( $image_src )['ext'];
    
            return array(
                'url'   => $image_src,
                'alt'   => $alt_text ? $alt_text : '',  // Fallback if no alt text
                'type'  => $image_type, // Image type (e.g., jpg, png)
            );
        }
    
        return null;
    }
    
    // Hook the function to the REST API initialization action
    add_action( 'rest_api_init', 'custom_register_web_stories_route' );
    