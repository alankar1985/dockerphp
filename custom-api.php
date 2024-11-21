<?php
/*
Plugin Name: Custom API
Description: custom API for POST
Version: 1.0
Author: Alankar
*/


add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/posts', [
        'methods' => 'GET',
        'callback' => 'get_filtered_posts',
        'args' => [
            'post_type' => [
                'required' => false,
                'validate_callback' => function($param) {
                    return is_string($param);
                },
                'default' => 'post',
            ],
            'category' => [
                'required' => false,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
            ],
            'tags' => [
                'required' => false,
                'validate_callback' => function($param) {
                    return is_string($param);
                },
            ],
            'date_from' => [
                'required' => false,
                'validate_callback' => function($param) {
                    return is_string($param);
                },
            ],
            'date_to' => [
                'required' => false,
                'validate_callback' => function($param) {
                    return is_string($param);
                },
            ],
            'fields' => [
                'required' => false,
                'validate_callback' => function($param) {
                    return is_string($param);
                },
            ],
        ],
    ]);
});



function get_filtered_posts($request) {
    $post_type = $request->get_param('post_type') ?: 'post';

    $args = [
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ];

    // Filter by category (only applies to standard 'post' type)
    if ($request->get_param('category') && $post_type === 'post') {
        $args['cat'] = $request->get_param('category');
    }

    // Filter by tags (only applies to standard 'post' type)
    if ($request->get_param('tags') && $post_type === 'post') {
        $tags = explode(',', $request->get_param('tags'));
        $args['tag_slug__in'] = $tags;
    }

    // Filter by date range
    if ($request->get_param('date_from') || $request->get_param('date_to')) {
        $date_query = [];

        if ($request->get_param('date_from')) {
            $date_query['after'] = $request->get_param('date_from');
        }

        if ($request->get_param('date_to')) {
            $date_query['before'] = $request->get_param('date_to');
        }

        $date_query['inclusive'] = true;
        $args['date_query'] = [$date_query];
    }

    // Parse the 'fields' parameter
    $requested_fields = [];
    if ($request->get_param('fields')) {
        $requested_fields = array_map('trim', explode(',', $request->get_param('fields')));
    }

    $query = new WP_Query($args);
    $posts = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            // Prepare the post data based on requested fields
            $post_data = [];
            if (empty($requested_fields) || in_array('id', $requested_fields)) {
                $post_data['id'] = get_the_ID();
            }
            if (empty($requested_fields) || in_array('title', $requested_fields)) {
                $post_data['title'] = get_the_title();
            }
            if (empty($requested_fields) || in_array('excerpt', $requested_fields)) {
                $post_data['excerpt'] = get_the_excerpt();
            }
            if (empty($requested_fields) || in_array('date', $requested_fields)) {
                $post_data['date'] = get_the_date();
            }
            if (empty($requested_fields) || in_array('category', $requested_fields)) {
                $categories = get_the_category();
                $filtered_categories = [];
                foreach ($categories as $category) {
                    $filtered_categories[] = [
                        'id' => $category->term_id,
                        'name' => $category->name,
                        'slug' => $category->slug, // Set custom status here; change as needed
                    ];
                }
                $post_data['category'] = $filtered_categories;
            }
            if (empty($requested_fields) || in_array('tags', $requested_fields)) {
                $tags = get_the_tags();


                $filtered_tags = [];
                foreach ($tags as $tag) {
                    $filtered_tags[] = [
                        'id' => $tag->term_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug, // Set custom status here; change as needed
                    ];
                }
                $post_data['tag'] = $filtered_tags;
            }
            if (empty($requested_fields) || in_array('link', $requested_fields)) {
                $post_data['link'] = get_permalink();
            }

            $posts[] = $post_data;
        }
        wp_reset_postdata();
    }

    return new WP_REST_Response($posts, 200);
}

//======================= Modify existing post api to show tags and category name and slug instead of ids
add_action('rest_api_init', function () {
    // Register categories for standard post type
    register_rest_field('post', 'categories', [
        'get_callback' => 'get_post_categories',
        'schema' => null,
    ]);

    // Register categories for custom post type "blog"
    register_rest_field('blog', 'categories', [
        'get_callback' => 'get_post_categories',
        'schema' => null,
    ]);

    // Register tags for standard post type
    register_rest_field('post', 'tags', [
        'get_callback' => 'get_post_tags',
        'schema' => null,
    ]);

    // Register tags for custom post type "blog"
    register_rest_field('blog', 'tags', [
        'get_callback' => 'get_post_tags',
        'schema' => null,
    ]);

        // Register featured image details for standard post type
    register_rest_field('post', 'featured_image_details', [
            'get_callback' => 'get_featured_image_details',
            'schema' => null,
        ]);
    
        // Register featured image details for custom post type "blog"
    register_rest_field('blog', 'featured_image_details', [
            'get_callback' => 'get_featured_image_details',
            'schema' => null,
        ]);

   // Register author details for standard post type
    register_rest_field('post', 'author_details', [
        'get_callback' => 'get_post_author_details',
        'schema' => null,
    ]);
    
});

// Callback function to retrieve author details
function get_post_author_details($object, $field_name, $request) {
    $author_id = $object['author'];
    
    // Get the author's display name and avatar
    $author_name = get_the_author_meta('display_name', $author_id);
    $author_avatar = get_avatar_url($author_id);

    return [
        'id' => $author_id,
        'name' => $author_name,
        'avatar' => $author_avatar,
    ];
}

// Callback function to retrieve categories
function get_post_categories($object, $field_name, $request) {
    $categories = get_the_category($object['id']);
    $filtered_categories = [];

    foreach ($categories as $category) {
        $filtered_categories[] = [
            'id' => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug, // Modify this logic if needed
        ];
    }

    return $filtered_categories;
}

// Callback function to retrieve tags
function get_post_tags($object, $field_name, $request) {
    $tags = get_the_tags($object['id']);
    $filtered_tags = [];

    if ($tags) {
        foreach ($tags as $tag) {
            $filtered_tags[] = [
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug, // Modify this logic if needed
            ];
        }
    }

    return $filtered_tags;
}

// Callback function to retrieve featured image details
function get_featured_image_details($object, $field_name, $request) {
    $featured_image_id = get_post_thumbnail_id($object['id']);
    if (!$featured_image_id) {
        return null; // Return null if there's no featured image
    }

    $image_data = [];
    $image_data['id'] = $featured_image_id;
    
    // Get different image sizes with URLs
    $image_sizes = ['thumbnail', 'medium', 'large', 'full'];
    foreach ($image_sizes as $size) {
        $image_src = wp_get_attachment_image_src($featured_image_id, $size);
        if ($image_src) {
            $image_data['sizes'][$size] = $image_src[0];
        }
    }

    // Get the alt text
    $image_data['alt'] = get_post_meta($featured_image_id, '_wp_attachment_image_alt', true);

    return $image_data;
}


//=======================End:: Modify existing post api to show tags and category name and slug instead of ids