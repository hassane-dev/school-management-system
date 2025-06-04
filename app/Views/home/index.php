<?php // File: app/Views/home/index.php
// $data['page_specific_title'] is available if passed from controller
?>

<div class="jumbotron text-center">
    <?php if (isset($page_specific_title) && !empty($page_specific_title)): ?>
        <h1><?php echo htmlspecialchars($page_specific_title); ?></h1>
    <?php else: ?>
        <h1><?php echo __('welcome_message'); ?></h1>
    <?php endif; ?>
    <p class="lead"><?php echo __('current_language_is'); ?></p>
    <hr>
    <p>
        <a href="<?php echo URL_ROOT; ?>/home/about" class="btn btn-primary"><?php echo __('go_to_about'); ?></a>
    </p>
</div>

<div class="container">
    <p>This is the main content area of the homepage, demonstrating the MVC structure and i18n.</p>
    <p>The overall page title (in the browser tab) and the header/footer are part of the default layout, also using translated strings.</p>
</div>
