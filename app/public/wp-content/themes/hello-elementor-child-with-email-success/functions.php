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

    //$name = sanitize_text_field($_POST['reg_name']);
    //$email = sanitize_email($_POST['reg_email']);
    //$event_id = intval($_POST['event_id']);

    //global $wpdb;
    //$table = $wpdb->prefix . 'event_custom_registrations';

    //$wpdb->query(
        //"CREATE TABLE IF NOT EXISTS $table (
            //id BIGINT AUTO_INCREMENT PRIMARY KEY,
            //event_id BIGINT,
            //name VARCHAR(255),
            //email VARCHAR(255),
            //created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        //)"
    //);

    //$wpdb->insert($table, [
        //'event_id' => $event_id,
        //'name' => $name,
        //'email' => $email,
        //'event_title' => $event_title,
    //]);







    $name = sanitize_text_field($_POST['reg_name']);
$email = sanitize_email($_POST['reg_email']);
$event_id = intval($_POST['event_id']);

//Fetch the event title
$post = get_post($event_id);
$event_title = $post ? $post->post_title : 'Unknown Event';

global $wpdb;
$table = $wpdb->prefix . 'event_custom_registrations';

$wpdb->query(
    "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        event_id BIGINT,
        name VARCHAR(255),
        email VARCHAR(255),
        event_title VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )"
);

//Insert data including event_title
$wpdb->insert($table, [
    'event_id' => $event_id,
    'name' => $name,
    'email' => $email,
    'event_title' => $event_title,
    'created_at' => current_time('mysql')
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


//send email notification for events on the day they start
//function send_event_reminder_emails() {
//    global $wpdb;
//
//    $table = $wpdb->prefix . 'event_custom_registrations';
//    $today = gmdate('Y-m-d'); // UTC date
//
//    error_log("🔄 Checking for events happening on: $today");
//
//    $registrations = $wpdb->get_results($wpdb->prepare("
//        SELECT r.*, p.post_title, m.meta_value as event_start
//        FROM {$table} r
//        JOIN {$wpdb->prefix}posts p ON p.ID = r.event_id
//        JOIN {$wpdb->prefix}postmeta m ON m.post_id = r.event_id
//        WHERE m.meta_key = '_event_start_date'
//       AND DATE(m.meta_value) = %s
//        AND (r.reminder_sent IS NULL OR r.reminder_sent = 0)
//    ", $today));
//
//    if (empty($registrations)) {
//        error_log("🚫 No registrations found for events today ($today).");
//        return;
//    }

//    error_log("📦 Found " . count($registrations) . " registration(s)");
//    foreach ($registrations as $r) {
//        $start_time = date('l, F jS Y \a\t g:i A', strtotime($r->event_start));
//        $subject = "🔔 Reminder: {$r->post_title} is today!";
//        $message = "Hi {$r->name},\n\nJust a quick reminder that you registered for \"{$r->post_title}\" happening today at {$start_time}.\n\nSee you there!\n\n— Salem Events Hub";
//        $headers = ['Content-Type: text/plain; charset=UTF-8'];

//        wp_mail($r->email, $subject, $message, $headers);
//        $wpdb->update($table, ['reminder_sent' => 1], ['id' => $r->id]);

//        error_log("📨 Reminder sent to: {$r->email} for event: {$r->post_title}");
//    }

//    error_log("✅ Reminder process completed at " . current_time('mysql'));
//}

//ish
//if (!wp_next_scheduled('daily_event_email_reminders')) {
//    wp_schedule_event(time(), 'daily', 'daily_event_email_reminders');
//}
//add_action('daily_event_email_reminders', 'send_event_reminder_emails');




//send event reminders and sync to wp_notifications
function send_event_reminder_emails() {
    global $wpdb;

    $table = $wpdb->prefix . 'event_custom_registrations';
    $notifications_table = $wpdb->prefix . 'event_notifications';
    $today = gmdate('Y-m-d'); // UTC date

    error_log("🔄 Checking for events happening on: $today");

    $registrations = $wpdb->get_results($wpdb->prepare("
        SELECT r.*, p.post_title, m.meta_value as event_start
        FROM {$table} r
        JOIN {$wpdb->prefix}posts p ON p.ID = r.event_id
        JOIN {$wpdb->prefix}postmeta m ON m.post_id = r.event_id
        WHERE m.meta_key = '_event_start_date'
        AND DATE(m.meta_value) = %s
        AND (r.reminder_sent IS NULL OR r.reminder_sent = 0)
    ", $today));

    if (empty($registrations)) {
        error_log("🚫 No registrations found for events today ($today).");
        return;
    }

    error_log("📦 Found " . count($registrations) . " registration(s)");
    foreach ($registrations as $r) {
        $start_time = date('l, F jS Y \a\t g:i A', strtotime($r->event_start));
        $subject = "🔔 Reminder: {$r->post_title} is today!";
        $message = "Hi {$r->name},\n\nJust a quick reminder that you registered for \"{$r->post_title}\" happening today at {$start_time}.\n\nSee you there!\n\n— Salem Events Hub";
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        // Send the email
        $sent = wp_mail($r->email, $subject, $message, $headers);

        if ($sent) {
            // Mark reminder as sent
            $wpdb->update($table, ['reminder_sent' => 1], ['id' => $r->id]);

            // Insert into wp_event_notifications
            $wpdb->insert($notifications_table, [
                'user_email'  => $r->email,
                'event_id'    => $r->event_id,
                'event_title' => $r->post_title,
                'message'     => $message,
                'sent_at'     => current_time('mysql'),
            ]);

            error_log("📨 Reminder sent + logged for: {$r->email} | Event: {$r->post_title}");
        } else {
            error_log("❌ Email failed to send to: {$r->email}");
        }
    }

    error_log("✅ Reminder process completed at " . current_time('mysql'));
}

if (!wp_next_scheduled('daily_event_email_reminders')) {
    wp_schedule_event(time(), 'daily', 'daily_event_email_reminders');
}
add_action('daily_event_email_reminders', 'send_event_reminder_emails');


//ahhh

//4 1 25 google calendar ish
function seh_add_google_calendar_button_meta_section($event_id) {
    if (get_post_type($event_id) !== 'event_listing') return;

    $title = urlencode(get_the_title($event_id));
    $location = urlencode(get_post_meta($event_id, '_event_location', true));
    $description = urlencode(strip_tags(get_post_meta($event_id, '_event_description', true)));

    $start_timestamp = get_post_meta($event_id, '_event_start_date', true);
    $end_timestamp = get_post_meta($event_id, '_event_end_date', true);

    if (!$start_timestamp || !$end_timestamp) return;

    // Format without UTC (Z) so it uses site’s local time
    $start_date = date("Ymd\THis", strtotime($start_timestamp));
    $end_date = date("Ymd\THis", strtotime($end_timestamp));

    $google_calendar_url = "https://calendar.google.com/calendar/render?action=TEMPLATE";
    $google_calendar_url .= "&text={$title}";
    $google_calendar_url .= "&dates={$start_date}/{$end_date}";
    $google_calendar_url .= "&details={$description}";
    $google_calendar_url .= "&location={$location}";

    echo '<div class="event-google-calendar" style="margin: 10px 0;">';
    echo '<a href="' . esc_url($google_calendar_url) . '" target="_blank" rel="noopener noreferrer" class="google-calendar-button" style="display: inline-block; padding: 10px 15px; background-color: #4285F4; color: #fff; border-radius: 5px; text-decoration: none; font-weight: bold;">';
    echo '📅 Add to Google Calendar';
    echo '</a>';
    echo '</div>';
}
add_action('single_event_listing_meta_end', 'seh_add_google_calendar_button_meta_section');




//4 9 25 new syncing new events posted into wp_events
add_action('publish_event_listing', 'sync_event_listing_to_wp_events_full', 10, 2);

function sync_event_listing_to_wp_events_full($post_ID, $post) {
    global $wpdb;

    // Skip if already inserted
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}events WHERE event_id = %d", $post_ID
    ));
    if ($exists) return;

    $event_title = $post->post_title;
    $event_date  = get_post_meta($post_ID, '_event_start_date', true) ?: current_time('mysql');
    $event_location = get_post_meta($post_ID, '_event_location', true) ?: 'TBD';

    // Organizer
    $organizer_ids = get_post_meta($post_ID, '_event_organizer_ids', true);
    $organizer_id = is_array($organizer_ids) ? reset($organizer_ids) : (int) $organizer_ids;
    $organizer_name = 'SSU Organizer';
    $organizer_email = 'salemeventshub@gmail.com';

    if ($organizer_id) {
        $organizer = $wpdb->get_row($wpdb->prepare(
            "SELECT name, email FROM {$wpdb->prefix}event_organizers WHERE id = %d", $organizer_id
        ));
        if ($organizer) {
            $organizer_name = $organizer->name;
            $organizer_email = $organizer->email;
        }
    }

    // Get taxonomy values
    $event_type = 'Other';
    $event_category = 'General';

    $tax_query = $wpdb->get_results($wpdb->prepare("
        SELECT t.name, tt.taxonomy 
        FROM {$wpdb->prefix}term_relationships tr
        INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
        WHERE tr.object_id = %d
    ", $post_ID));

    foreach ($tax_query as $term) {
        if ($term->taxonomy === 'event_listing_type') {
            $event_type = $term->name;
        } elseif ($term->taxonomy === 'event_listing_category') {
            $event_category = $term->name;
        }
    }

    // Insert into custom wp_events table
    $wpdb->insert(
        "{$wpdb->prefix}events",
        [
            'event_id'        => $post_ID,
            'event_title'     => $event_title,
            'event_date'      => $event_date,
            'event_location'  => $event_location,
            'event_type'      => $event_type,
            'event_category'  => $event_category,
            'organizer_id'    => $organizer_id,
            'organizer_name'  => $organizer_name,
            'organizer_email' => $organizer_email,
        ]
    );
}







?>