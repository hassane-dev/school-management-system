<?php // File: app/Views/home/about.php
// $data['page_specific_title'] is available if passed from controller
?>

<div class="container mt-4">
    <h2><?php echo isset($page_specific_title) ? htmlspecialchars($page_specific_title) : __('about_us_title'); ?></h2>
    <p><?php echo __('about_us_content'); ?></p>

    <?php
    // Display message from form submission (if any)
    if (isset($_GET['message'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars(urldecode($_GET['message'])) . '</div>';
    }
    ?>

    <form action="<?php echo URL_ROOT; ?>/home/submit_contact" method="POST" class="mt-4 p-4 border rounded">
        <div class="form-group">
            <label for="name"><?php echo __('form_label_name'); ?>:</label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="email"><?php echo __('form_label_email'); ?>:</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="message"><?php echo __('form_label_message'); ?>:</label>
            <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo __('form_button_submit'); ?></button>
    </form>

    <p class="mt-4"><a href="<?php echo URL_ROOT; ?>/home/index"><?php echo __('go_back_home'); ?></a></p>
</div>
