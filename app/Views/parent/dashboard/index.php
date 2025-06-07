<?php
// $data['title']
// $data['user']
?>
<div class="container mt-4">
    <h1><?php echo htmlspecialchars($title ?? __('parent_dashboard_title_default', "Parent's Dashboard")); ?></h1>
    <p><?php echo __('parent_dashboard_welcome', ['name' => htmlspecialchars($user->nom ?? 'Parent')]); ?></p>
    <p><?php echo __('parent_dashboard_info', "Follow your child's progress, attendance, and communicate with teachers."); ?></p>

    <?php // Links to parent-specific features ?>
    <ul>
        <li><a href="#"><?php echo __('parent_link_child_progress', "Child's Progress"); ?></a></li>
        <li><a href="#"><?php echo __('parent_link_attendance', 'Attendance Records'); ?></a></li>
        <li><a href="#"><?php echo __('parent_link_teacher_communication', 'Teacher Communication'); ?></a></li>
    </ul>
</div>
