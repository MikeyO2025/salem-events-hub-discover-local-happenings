<?php
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
        $events = [];

        $args = array(
            'post_type' => 'event_listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );
        $query = new WP_Query($args);

        while ($query->have_posts()) {
            $query->the_post();
            $event_id = get_the_ID();

            $start_date = get_post_meta($event_id, '_event_start_date', true);
            $end_date   = get_post_meta($event_id, '_event_end_date', true);

            $start = date('c', strtotime($start_date));
            $end = $end_date ? date('c', strtotime($end_date)) : $start;

            $events[] = [
                'title' => get_the_title(),
                'start' => $start,
                'end'   => $end,
                'url'   => get_permalink($event_id),
            ];
        }

        wp_reset_postdata();
        wp_send_json($events);
    }
}
?>
