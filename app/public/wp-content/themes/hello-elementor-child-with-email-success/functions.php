<?php
// Enqueue parent theme styles
add_action('wp_enqueue_scripts', 'enqueue_hello_parent');
function enqueue_hello_parent() {
    wp_enqueue_style('hello-parent-style', get_template_directory_uri() . '/style.css');
}

// Show success message
add_action('wp_footer', 'custom_registration_success_message');
function custom_registration_success_message() {
    if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
        echo '<div style="position:fixed;bottom:0;left:0;width:100%;background:green;color:white;text-align:center;padding:10px;z-index:9999;">🎉 Registration successful! Check your email for confirmation.</div>';
    }
}

// Handle custom form submissions
add_action('admin_post_submit_event_registration', 'handle_custom_event_registration');
add_action('admin_post_nopriv_submit_event_registration', 'handle_custom_event_registration');

function handle_custom_event_registration() {
    if (
        !isset($_POST['reg_name'], $_POST['reg_email'], $_POST['event_id']) ||
        !is_email($_POST['reg_email'])
    ) {
        wp_die('Invalid form data.');
    }

    $name = sanitize_text_field($_POST['reg_name']);
    $email = sanitize_email($_POST['reg_email']);
    $event_id = intval($_POST['event_id']);

    global $wpdb;
    $table = $wpdb->prefix . 'event_custom_registrations';

    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            event_id BIGINT,
            name VARCHAR(255),
            email VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $wpdb->insert($table, [
        'event_id' => $event_id,
        'name' => $name,
        'email' => $email,
    ]);

    // Send confirmation email
    $subject = 'Your Event Registration was Successful!';
    $message = 'Hi ' . $name . ",\n\nThank you for registering for the event. We have received your registration details.\n\nSee you there!\n\n— Salem Events Hub";
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    wp_mail($email, $subject, $message, $headers);

    wp_redirect(get_permalink($event_id) . '?registration=success');
    exit;
}

//new ish 
// Load FullCalendar CSS/JS
function load_fullcalendar_assets() {
    wp_enqueue_style('fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css');
    wp_enqueue_script('fullcalendar-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js', array(), '6.1.8', true);
}
add_action('wp_enqueue_scripts', 'load_fullcalendar_assets');

// AJAX handler
add_action('wp_ajax_get_calendar_events', 'get_calendar_events');
add_action('wp_ajax_nopriv_get_calendar_events', 'get_calendar_events');

if (!function_exists('get_calendar_events')) {
    function get_calendar_events() {
        $args = [
            'post_type' => 'event_listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ];

        $tax_query = [];

        // Apply filters only if they're present
        if (!empty($_POST['event_type'])) {
            $tax_query[] = [
                'taxonomy' => 'event_listing_type',
                'field' => 'slug',
                'terms' => sanitize_text_field($_POST['event_type']),
            ];
        }

        if (!empty($_POST['event_category'])) {
            $tax_query[] = [
                'taxonomy' => 'event_listing_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($_POST['event_category']),
            ];
        }

        // If we have filters, add to args
        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($args);
        $events = [];

        while ($query->have_posts()) {
            $query->the_post();

            $start = get_post_meta(get_the_ID(), '_event_start_date', true);
            $end   = get_post_meta(get_the_ID(), '_event_end_date', true);

            $events[] = [
                'title' => get_the_title(),
                'start' => $start,
                'end'   => $end,
                'url'   => get_permalink(),
            ];
        }

        wp_reset_postdata();
        wp_send_json($events);
    }
}


//new ish 3 31 Enable custom event taxonomies for REST API
function expose_event_taxonomies_to_rest() {
    register_taxonomy('event_type', 'event_listing', [
        'label' => 'Event Types',
        'public' => true,
        'rewrite' => ['slug' => 'event_type'],
        'hierarchical' => false,
        'show_in_rest' => true,
    ]);

    register_taxonomy('event_category', 'event_listing', [
        'label' => 'Event Categories',
        'public' => true,
        'rewrite' => ['slug' => 'event_category'],
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
}
add_action('init', 'expose_event_taxonomies_to_rest');


?>