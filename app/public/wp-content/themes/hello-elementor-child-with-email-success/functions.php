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
?>
