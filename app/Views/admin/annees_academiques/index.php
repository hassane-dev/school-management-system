<?php // File: app/Views/admin/annees_academiques/index.php
// $data['annees']
// $data['activeYear']
// $data['title']
// All text should use __() for i18n.
?>

<div class="container mt-4">
    <h1><?php echo htmlspecialchars($title ?? __('aa_title_list_default', 'Academic Years')); ?></h1>
    <p>
        <a href="<?php echo URL_ROOT; ?>/admin/anneesacademiques/add" class="btn btn-success">
            <i class="fas fa-plus"></i> <?php echo __('aa_add_new_button', 'Add New Academic Year'); ?>
        </a>
    </p>

    <?php
    // Flash message display (already in default.php layout, but can be here if specific placement needed)
    // if (isset($_SESSION['flash_message'])) {
    //     echo '<div class="alert alert-' . htmlspecialchars($_SESSION['flash_message']['type']) . '">' . htmlspecialchars($_SESSION['flash_message']['text']) . '</div>';
    //     unset($_SESSION['flash_message']);
    // }
    ?>

    <?php if ($activeYear): ?>
        <div class="alert alert-info">
            <?php echo __('aa_current_active_year', 'Current Active Year:'); ?>
            <strong><?php echo htmlspecialchars($activeYear->libelle); ?></strong>
            (<?php echo htmlspecialchars(date(DEFAULT_DATE_FORMAT ?? 'Y-m-d', strtotime($activeYear->date_debut))); ?> -
             <?php echo htmlspecialchars(date(DEFAULT_DATE_FORMAT ?? 'Y-m-d', strtotime($activeYear->date_fin))); ?>)
        </div>
    <?php else: ?>
        <div class="alert alert-warning"><?php echo __('aa_no_active_year_set', 'No academic year is currently active.'); ?></div>
    <?php endif; ?>


    <?php if (!empty($annees)): ?>
    <table class="table table-striped table-bordered">
        <thead class="thead-light">
            <tr>
                <th><?php echo __('aa_col_libelle', 'Label'); ?></th>
                <th><?php echo __('aa_col_date_debut', 'Start Date'); ?></th>
                <th><?php echo __('aa_col_date_fin', 'End Date'); ?></th>
                <th><?php echo __('aa_col_active', 'Active'); ?></th>
                <th><?php echo __('aa_col_actions', 'Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($annees as $annee): ?>
            <tr class="<?php echo $annee->active ? 'table-success' : ''; ?>">
                <td><?php echo htmlspecialchars($annee->libelle); ?></td>
                <td><?php echo htmlspecialchars(date(DEFAULT_DATE_FORMAT ?? 'Y-m-d', strtotime($annee->date_debut))); ?></td>
                <td><?php echo htmlspecialchars(date(DEFAULT_DATE_FORMAT ?? 'Y-m-d', strtotime($annee->date_fin))); ?></td>
                <td>
                    <?php if ($annee->active): ?>
                        <span class="badge badge-success"><?php echo __('global_yes', 'Yes'); ?></span>
                    <?php else: ?>
                        <span class="badge badge-secondary"><?php echo __('global_no', 'No'); ?></span>
                    <?php endif; ?>
                </td>
                <td class="action-buttons">
                    <?php if (!$annee->active): ?>
                        <a href="<?php echo URL_ROOT; ?>/admin/anneesacademiques/activate/<?php echo $annee->id; ?>" class="btn btn-sm btn-info" title="<?php echo __('aa_action_activate', 'Activate'); ?>">
                            <i class="fas fa-check-circle"></i>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo URL_ROOT; ?>/admin/anneesacademiques/edit/<?php echo $annee->id; ?>" class="btn btn-sm btn-warning" title="<?php echo __('global_edit_button', 'Edit'); ?>">
                        <i class="fas fa-edit"></i>
                    </a>
                    <?php if (!$annee->active): // Prevent deleting active year from this direct link ?>
                    <form action="<?php echo URL_ROOT; ?>/admin/anneesacademiques/delete/<?php echo $annee->id; ?>" method="POST" style="display:inline;" onsubmit="return confirm('<?php echo __('global_confirm_delete_generic', 'Are you sure you want to delete this item?'); ?>')">
                        <button type="submit" class="btn btn-sm btn-danger" title="<?php echo __('global_delete_button', 'Delete'); ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p><?php echo __('aa_no_years_found', 'No academic years found. Please add one.'); ?></p>
    <?php endif; ?>
</div>

<!-- Font Awesome for icons - Ensure it's loaded in your main layout or here -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css"> -->
