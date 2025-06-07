<?php
// $data['title']
// $data['user']
?>
<div class="container mt-4">
    <h1><?php echo htmlspecialchars($title ?? __('etudiant_dashboard_title_default', 'Student Dashboard')); ?></h1>
    <p><?php echo __('etudiant_dashboard_welcome', ['name' => htmlspecialchars($user->nom ?? 'Student')]); ?></p>
    <p><?php echo __('etudiant_dashboard_info', 'Access your grades, schedule, and course materials here.'); ?></p>

    <?php // Links to student-specific features ?>
    <ul>
        <li><a href="#"><?php echo __('etudiant_link_my_grades', 'My Grades'); ?></a></li>
        <li><a href="#"><?php echo __('etudiant_link_my_schedule', 'My Schedule'); ?></a></li>
        <li><a href="#"><?php echo __('etudiant_link_course_materials', 'Course Materials'); ?></a></li>
    </ul>
</div>
