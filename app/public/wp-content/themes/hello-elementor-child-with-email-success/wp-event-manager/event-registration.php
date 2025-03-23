<?php
/**
 * Template for event registration section.
 */

if (!is_singular('event_listing')) {
    return;
}

global $post;

$hide = get_post_meta($post->ID, '_show_hide_registration_button', true);
if ($hide == 1) {
    return;
}
?>

<div class="custom-event-registration-form">
    <h3>Register for this Event</h3>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="submit_event_registration">
        <input type="hidden" name="event_id" value="<?php echo esc_attr($post->ID); ?>">

        <p>
            <label for="reg_name">Name</label><br>
            <input type="text" name="reg_name" required>
        </p>
        <p>
            <label for="reg_email">Email</label><br>
            <input type="email" name="reg_email" required>
        </p>
        <p>
            <input type="submit" value="Submit Registration">
        </p>
    </form>
</div>
