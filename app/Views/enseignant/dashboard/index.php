<?php
// $data['title']
// $data['user'] (current user object from Auth::getCurrentUser())
?>
<div class="container mt-4">
    <h1><?php echo htmlspecialchars($title ?? __('enseignant_dashboard_title_default', 'Teacher Dashboard')); ?></h1>
    <p><?php echo __('enseignant_dashboard_welcome', ['name' => htmlspecialchars($user->nom ?? 'Teacher')]); ?></p>
    <p><?php echo __('enseignant_dashboard_info', 'Here you can manage your classes, students, and grades.'); ?></p>

    <?php // Links to teacher-specific features will go here ?>
    <ul>
        <li><a href="#"><?php echo __('enseignant_link_my_classes', 'My Classes'); ?></a></li>
        <li><a href="#"><?php echo __('enseignant_link_my_students', 'My Students'); ?></a></li>
        <li><a href="#"><?php echo __('enseignant_link_grading', 'Grading'); ?></a></li>
    </ul>
</div>
