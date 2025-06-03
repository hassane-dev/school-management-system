<?php
// Data available: $annees_academiques_list, $active_annee from index.php (passed from controller)
?>

<div class="pt-3">
    <h4><?php echo __('academic_years_title', 'Années Académiques'); ?></h4>

    <button type="button" class="btn btn-success mb-3" id="addAnneeButton" data-toggle="modal" data-target="#addEditAnneeModal" data-action="<?php echo base_url('parametres/add_annee_academique'); ?>">
        <i class="fas fa-plus"></i> <?php echo __('add_academic_year_btn', 'Ajouter Année Académique'); ?>
    </button>

    <?php if ($active_annee): ?>
        <div class="alert alert-info" role="alert">
            <?php echo __('current_active_year_is', 'L\'année académique active est :'); ?> <strong><?php echo htmlspecialchars($active_annee->libelle); ?></strong>
            (<?php echo htmlspecialchars($active_annee->date_debut); ?> - <?php echo htmlspecialchars($active_annee->date_fin); ?>)
        </div>
    <?php else: ?>
        <div class="alert alert-warning" role="alert">
            <?php echo __('no_active_academic_year', 'Aucune année académique n\'est actuellement active.'); ?>
        </div>
    <?php endif; ?>

    <table class="table table-striped table-bordered">
        <thead class="thead-light">
            <tr>
                <th><?php echo __('label_academic_year', 'Libellé'); ?></th>
                <th><?php echo __('start_date', 'Date Début'); ?></th>
                <th><?php echo __('end_date', 'Date Fin'); ?></th>
                <th><?php echo __('status', 'Statut'); ?></th>
                <th><?php echo __('actions', 'Actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($annees_academiques_list)): ?>
                <?php foreach ($annees_academiques_list as $annee): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($annee->libelle); ?></td>
                        <td><?php echo htmlspecialchars($annee->date_debut); ?></td>
                        <td><?php echo htmlspecialchars($annee->date_fin); ?></td>
                        <td>
                            <?php if ($annee->active): ?>
                                <span class="badge badge-success"><?php echo __('active_status', 'Active'); ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?php echo __('inactive_status', 'Inactive'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="action-buttons">
                            <button type="button" class="btn btn-sm btn-warning edit-annee-button"
                                    data-toggle="modal" data-target="#addEditAnneeModal"
                                    data-id="<?php echo $annee->id; ?>"
                                    data-libelle="<?php echo htmlspecialchars($annee->libelle); ?>"
                                    data-date_debut="<?php echo htmlspecialchars($annee->date_debut); ?>"
                                    data-date_fin="<?php echo htmlspecialchars($annee->date_fin); ?>"
                                    data-active="<?php echo $annee->active; ?>"
                                    data-action="<?php echo base_url('parametres/update_annee_academique/' . $annee->id); ?>">
                                <i class="fas fa-edit"></i> <?php echo __('edit_btn', 'Modifier'); ?>
                            </button>

                            <form action="<?php echo base_url('parametres/delete_annee_academique/' . $annee->id); ?>" method="POST" onsubmit="return confirm('<?php echo __('confirm_delete_academic_year', 'Êtes-vous sûr de vouloir supprimer cette année académique ?'); ?>');">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> <?php echo __('delete_btn', 'Supprimer'); ?>
                                </button>
                            </form>

                            <?php if (!$annee->active): ?>
                                <form action="<?php echo base_url('parametres/activer_annee_academique/' . $annee->id); ?>" method="POST">
                                    <button type="submit" class="btn btn-sm btn-info">
                                        <i class="fas fa-check"></i> <?php echo __('activate_btn', 'Activer'); ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                 <form action="<?php echo base_url('parametres/desactiver_annee_academique/' . $annee->id); ?>" method="POST">
                                    <button type="submit" class="btn btn-sm btn-secondary" <?php // Consider disabling if it's the only active one and one must be active ?>>
                                        <i class="fas fa-times"></i> <?php echo __('deactivate_btn', 'Désactiver'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center"><?php echo __('no_academic_years_found', 'Aucune année académique trouvée.'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal for Add/Edit Academic Year -->
<div class="modal fade" id="addEditAnneeModal" tabindex="-1" role="dialog" aria-labelledby="addEditAnneeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addEditAnneeForm" method="POST" action=""> <!-- Action will be set by JS -->
                <div class="modal-header">
                    <h5 class="modal-title" id="addEditAnneeModalLabel"><?php echo __('add_academic_year', 'Ajouter Année Académique'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="annee_id"> <!-- For editing -->
                    <div class="form-group">
                        <label for="libelle"><?php echo __('label_academic_year', 'Libellé'); ?></label>
                        <input type="text" class="form-control" name="libelle" id="annee_libelle" required>
                    </div>
                    <div class="form-group">
                        <label for="date_debut"><?php echo __('start_date', 'Date Début'); ?></label>
                        <input type="date" class="form-control" name="date_debut" id="annee_date_debut" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin"><?php echo __('end_date', 'Date Fin'); ?></label>
                        <input type="date" class="form-control" name="date_fin" id="annee_date_fin" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="active" id="annee_active" value="1">
                        <label class="form-check-label" for="annee_active"><?php echo __('is_active_q', 'Active ?'); ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo __('close_btn', 'Fermer'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('save_btn', 'Enregistrer'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Font Awesome for icons (if not already included in main layout) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
