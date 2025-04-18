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

//415 new
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

    // Fetch the event title
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

    // Insert data including event_title
    $wpdb->insert($table, [
        'event_id' => $event_id,
        'name' => $name,
        'email' => $email,
        'event_title' => $event_title,
        'created_at' => current_time('mysql')
    ]);

    // ✅ Personalized confirmation email to user
    $subject = '✅ Registration Confirmed: ' . $event_title;
    $event_start_raw = get_post_meta($event_id, '_event_start_date', true);
    $event_start = $event_start_raw ? date('l, F jS Y \a\t g:i A', strtotime($event_start_raw)) : 'TBD';

    $message = "Hi {$name},\n\nThank you for registering for:\n📅 {$event_title}\n🕒 When: {$event_start}\n\nWe’ve received your registration and look forward to seeing you there!\n\n— Salem Events Hub";

    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    wp_mail($email, $subject, $message, $headers);

    // ✅ Notify both registration_email and organizer_email
    $registration_email = get_post_meta($event_id, '_registration', true);

    $organizer_ids = get_post_meta($event_id, '_event_organizer_ids', true);
    $organizer_id = is_array($organizer_ids) ? reset($organizer_ids) : (int) $organizer_ids;
    $organizer_email = get_post_meta($organizer_id, '_organizer_email', true);

    $notification_emails = array_filter([
        is_email($registration_email) ? $registration_email : null,
        is_email($organizer_email) ? $organizer_email : null
    ]);

    if (!empty($notification_emails)) {
        $organizer_subject = '📥 New Registration for Your Event: ' . $event_title;
        $organizer_message = "Hello,\n\nSomeone just registered for your event: \"{$event_title}\".\n\nRegistrant Info:\n- Name: {$name}\n- Email: {$email}\n\nYou can follow up or manage your registrations in the dashboard.\n\n— Salem Events Hub";

        foreach ($notification_emails as $notify_email) {
            wp_mail($notify_email, $organizer_subject, $organizer_message, $headers);
        }
    }

    wp_redirect(get_permalink($event_id) . '?registration=success');
    exit;
}







//old ish dont need anymore
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
//add_action('publish_event_listing', 'sync_event_listing_to_wp_events_full', 10, 2);

//function sync_event_listing_to_wp_events_full($post_ID, $post) {
//    global $wpdb;
//
    // Skip if already synced
//    $exists = $wpdb->get_var($wpdb->prepare(
//        "SELECT COUNT(*) FROM {$wpdb->prefix}events WHERE event_id = %d", $post_ID
//    ));
//    if ($exists) return;
//
//    $event_title    = $post->post_title;
//    $event_date     = get_post_meta($post_ID, '_event_start_date', true) ?: current_time('mysql');
//    $event_location = get_post_meta($post_ID, '_event_location', true) ?: 'TBD';

    // Organizer info from postmeta (serialized array of post IDs)
//    $organizer_ids = get_post_meta($post_ID, '_event_organizer_ids', true);
//    $organizer_id = is_array($organizer_ids) ? reset($organizer_ids) : (int) $organizer_ids;

//    $organizer_name  = 'SSU Organizer';
//    $organizer_email = 'salemeventshub@gmail.com';

//    if ($organizer_id) {
        // Pull from wp_posts and wp_postmeta
//        $post_obj = get_post($organizer_id);
 //       $email    = get_post_meta($organizer_id, '_organizer_email', true);

 //      if ($post_obj && $post_obj->post_type === 'event_organizer') {
  //          $organizer_name  = $post_obj->post_title;
  //          $organizer_email = $email ?: $organizer_email;
 //       }
  //  }

    // Default taxonomy labels
//    $event_type     = 'Other';
 //   $event_category = 'General';

    // Fetch taxonomy terms
//    $terms = $wpdb->get_results($wpdb->prepare("
//        SELECT t.name, tt.taxonomy
//        FROM {$wpdb->prefix}term_relationships tr
//        INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
//        INNER JOIN {$wpdb->prefix}terms t ON t.term_id = tt.term_id
//        WHERE tr.object_id = %d
//    ", $post_ID));

//    foreach ($terms as $term) {
//        if ($term->taxonomy === 'event_listing_type') {
 //           $event_type = $term->name;
 //       } elseif ($term->taxonomy === 'event_listing_category') {
//            $event_category = $term->name;
 //       }
//    }

    // Insert into wp_events
//    $wpdb->insert(
//        "{$wpdb->prefix}events",
 //       [
 //           'event_id'        => $post_ID,
  //          'event_title'     => $event_title,
  //          'event_date'      => $event_date,
  //          'event_location'  => $event_location,
  //          'event_type'      => $event_type,
  //          'event_category'  => $event_category,
  //          'organizer_id'    => $organizer_id,
  //          'organizer_name'  => $organizer_name,
  //          'organizer_email' => $organizer_email,
  //      ]
 //   );
//}







//update to commented above 4 13 25
add_action('publish_event_listing', 'sync_event_listing_to_wp_events_full', 10, 2);

function sync_event_listing_to_wp_events_full($post_ID, $post) {
    global $wpdb;

    $event_title    = $post->post_title;
    $event_date     = get_post_meta($post_ID, '_event_start_date', true) ?: current_time('mysql');
//    $event_location = get_post_meta($post_ID, '_event_location', true) ?: 'TBD';

//new ish 4 13 replacing above to online isnt null    

    $event_location = get_post_meta($post_ID, '_event_location', true);

        // Check if marked as online
        $is_online = get_post_meta($post_ID, '_event_online', true);
        if ($is_online === 'yes') {
             $event_location = 'Online';
        }

        if (empty($event_location)) {
            $event_location = 'TBD';
        }
//end newish 4 13


    // Organizer info
    $organizer_ids = get_post_meta($post_ID, '_event_organizer_ids', true);
    $organizer_id = is_array($organizer_ids) ? reset($organizer_ids) : (int) $organizer_ids;

    $organizer_name  = 'Organizer Not Set';
    $organizer_email = '';

    if ($organizer_id) {
        $post_obj = get_post($organizer_id);
        $email    = get_post_meta($organizer_id, '_organizer_email', true);
        if ($post_obj && $post_obj->post_type === 'event_organizer') {
            $organizer_name  = $post_obj->post_title;
            $organizer_email = $email ?: '';
        }
    }

    // Registration email fallback
    $registration_email = get_post_meta($post_ID, '_registration', true);
    if (empty($organizer_email)) {
        $organizer_email = $registration_email ?: 'not_provided@noemail.com';
    }

    // Event Type & Category
    $event_type = 'Other';
    $event_category = 'General';
    $terms = $wpdb->get_results($wpdb->prepare("
        SELECT t.name, tt.taxonomy
        FROM {$wpdb->prefix}term_relationships tr
        INNER JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->prefix}terms t ON t.term_id = tt.term_id
        WHERE tr.object_id = %d
    ", $post_ID));

    foreach ($terms as $term) {
        if ($term->taxonomy === 'event_listing_type') {
            $event_type = $term->name;
        } elseif ($term->taxonomy === 'event_listing_category') {
            $event_category = $term->name;
        }
    }

    // Insert or Replace into wp_events
    $wpdb->replace("{$wpdb->prefix}events", [
        'event_id'           => $post_ID,
        'event_title'        => $event_title,
        'event_date'         => $event_date,
        'event_location'     => $event_location,
        'event_type'         => $event_type,
        'event_category'     => $event_category,
        'organizer_id'       => $organizer_id ?: 0,
        'organizer_name'     => $organizer_name,
        'organizer_email'    => $organizer_email,
        'registration_email' => $registration_email,
    ]);
}






//commenting out below
//4 9 25 trying to auto sync organizers into wp_organizers table
// Queue organizer sync after full post save
//add_action('save_post_event_organizer', function ($post_ID, $post, $update) {
//    if ($post->post_status !== 'publish') return;

    // Defer sync to after save is complete
//    add_action('shutdown', function () use ($post_ID, $post) {
//        global $wpdb;

        // Avoid duplicates
//        $exists = $wpdb->get_var($wpdb->prepare(
//            "SELECT COUNT(*) FROM {$wpdb->prefix}event_organizers WHERE organizer_id = %d", $post_ID
//        ));
//        if ($exists) return;

        // Now meta should be saved — grab it
//        $organizer_email = get_post_meta($post_ID, '_organizer_email', true);
//        if (empty($organizer_email)) {
//            $organizer_email = 'unknown@unknown.com';
 //       }

//        $wpdb->insert("{$wpdb->prefix}event_organizers", [
 //           'organizer_id'    => $post_ID,
 //           'organizer_name'  => $post->post_title,
//            'organizer_email' => $organizer_email
//        ]);
//    });
//}, 10, 3);



//415 organizer updating 
add_action('save_post_event_organizer', function ($post_ID, $post, $update) {
    if ($post->post_status !== 'publish') return;

    add_action('shutdown', function () use ($post_ID, $post) {
        global $wpdb;

        $organizer_email = get_post_meta($post_ID, '_organizer_email', true);
        if (empty($organizer_email)) {
            $organizer_email = 'unknown@unknown.com';
        }

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}event_organizers WHERE organizer_id = %d", $post_ID
        ));

        if ($exists) {
            // UPDATE if it exists
            $wpdb->update("{$wpdb->prefix}event_organizers", [
                'organizer_name'  => $post->post_title,
                'organizer_email' => $organizer_email
            ], [
                'organizer_id' => $post_ID
            ]);
        } else {
            // INSERT if new
            $wpdb->insert("{$wpdb->prefix}event_organizers", [
                'organizer_id'    => $post_ID,
                'organizer_name'  => $post->post_title,
                'organizer_email' => $organizer_email
            ]);
        }
    });
}, 10, 3);







//added stuff above this
//add ish 41325 syncing user into user contact info
// First, try WP Everest's hook
add_action('user_registration_after_user_meta_update_action', 'seh_sync_user_contact_info_smart', 10, 2);

// Fallback: if Everest doesn’t run, use WP's native user_register + shutdown
add_action('user_register', function($user_id) {
    add_action('shutdown', function () use ($user_id) {
        seh_sync_user_contact_info_smart($user_id);
    });
});

// Unified sync function
function seh_sync_user_contact_info_smart($user_id, $form_data = null) {
    $user_info = get_userdata($user_id);

    if (!$user_info || !isset($user_info->user_login)) {
        error_log("❌ Could not load user info for ID: $user_id");
        return;
    }

    $user_login    = $user_info->user_login;
    $email         = $user_info->user_email;
    $role          = isset($user_info->roles[0]) ? $user_info->roles[0] : 'subscriber';

    $first_name    = get_user_meta($user_id, 'first_name', true);
    $last_name     = get_user_meta($user_id, 'last_name', true);
    $phone_number  = get_user_meta($user_id, 'user_registration_textarea_1740157275', true);

    error_log("📥 Final sync: $user_login | $first_name $last_name | $phone_number");

    global $wpdb;

    $wpdb->insert('wp_user_contact_info', [
        'user_id'      => $user_id,
        'user_login'   => $user_login,
        'user_email'   => $email,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'phone_number' => $phone_number,
        'role'         => $role
    ]);
}







//add updates when users edit profile
add_action('profile_update', 'seh_update_user_contact_info_on_profile_edit', 10, 2);

function seh_update_user_contact_info_on_profile_edit($user_id, $old_user_data) {
    $user_info = get_userdata($user_id);

    if (!$user_info) {
        error_log("⚠️ Profile update failed — no user info for ID $user_id");
        return;
    }

    $user_login    = $user_info->user_login;
    $email         = $user_info->user_email;
    $role          = isset($user_info->roles[0]) ? $user_info->roles[0] : 'subscriber';

    $first_name    = get_user_meta($user_id, 'first_name', true);
    $last_name     = get_user_meta($user_id, 'last_name', true);
    $phone_number  = get_user_meta($user_id, 'user_registration_textarea_1740157275', true);

    error_log("✏️ Profile updated: $user_login | $first_name $last_name | $phone_number");

    global $wpdb;

    // Check if row exists first
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM wp_user_contact_info WHERE user_id = %d", $user_id
    ));

    if ($exists) {
        // Update existing row
        $wpdb->update('wp_user_contact_info', [
            'user_email'   => $email,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'phone_number' => $phone_number,
            'role'         => $role
        ], ['user_id' => $user_id]);
    } else {
        // Insert fresh row (fallback)
        $wpdb->insert('wp_user_contact_info', [
            'user_id'      => $user_id,
            'user_login'   => $user_login,
            'user_email'   => $email,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'phone_number' => $phone_number,
            'role'         => $role
        ]);
    }
}




//hide toolbar for all but admin
add_filter('show_admin_bar', function($show) {
    if (!current_user_can('administrator')) {
        return false;
    }
    return $show;
});






?>