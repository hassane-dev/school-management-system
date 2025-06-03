<!DOCTYPE html>
<html lang="<?php echo CURRENT_LANG; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('settings_page_title', 'Gestion École'); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body { padding-top: 20px; }
        .container { max-width: 960px; }
        .tab-content { border: 1px solid #dee2e6; border-top: none; padding: 15px; }
        .display-section { margin-bottom: 15px; }
        .edit-form-section { display: none; margin-top: 20px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;}
        .action-buttons form { display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-end mb-2">
            <a href="?lang=en" class="mr-2">English</a>
            <a href="?lang=fr" class="mr-2">Français</a>
        </div>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
            ?>
        <?php endif; ?>

        <!-- Content will be loaded here by the controller's view method -->
        <?php echo $content_for_layout; ?>

    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function(){
            // Tab Management: Bootstrap handles basic tab switching.
            // For active tab persistence (optional, not strictly required by task):
            // If there's a hash in the URL, activate the corresponding tab
            var hash = window.location.hash;
            if (hash) {
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }
            // When a tab is shown, update the URL hash
            $('.nav-tabs a').on('shown.bs.tab', function (e) {
                if(history.pushState) {
                    history.pushState(null, null, e.target.hash);
                } else {
                    window.location.hash = e.target.hash;
                }
            });

            // Form Toggling for General and School Settings
            $('.toggle-edit-form').on('click', function(){
                var sectionId = $(this).data('section-id');
                $('#display-' + sectionId).hide();
                $('#edit-' + sectionId).show();
                $(this).hide(); // Hide the "Modifier" button itself
            });

            $('.cancel-edit-form').on('click', function(){
                var sectionId = $(this).data('section-id');
                $('#edit-' + sectionId).hide();
                $('#display-' + sectionId).show();
                $('.toggle-edit-form[data-section-id="' + sectionId + '"]').show(); // Show the "Modifier" button again
            });

            // Academic Years Modal Management
            var anneeModal = $('#addEditAnneeModal');
            var anneeForm = $('#addEditAnneeForm');
            var anneeModalLabel = $('#addEditAnneeModalLabel');
            var originalAddAction = "<?php echo base_url('parametres/add_annee_academique'); ?>"; // Store original action

            // For "Ajouter Année Académique" button
            $('#addAnneeButton').on('click', function(){
                anneeModalLabel.text('<?php echo __("add_academic_year_modal_title", "Ajouter une Année Académique"); ?>');
                anneeForm.attr('action', originalAddAction);
                anneeForm.find('input[name="id"]').val(''); // Clear ID field for add mode
                anneeForm.trigger('reset'); // Reset all form fields
                anneeForm.find('input[name="active"]').prop('checked', false); // Explicitly uncheck
                anneeModal.modal('show');
            });

            // For "Modifier" buttons in the academic years table
            $('.edit-annee-button').on('click', function(){
                var id = $(this).data('id');
                var libelle = $(this).data('libelle');
                var dateDebut = $(this).data('date_debut');
                var dateFin = $(this).data('date_fin');
                var active = $(this).data('active');
                var updateAction = $(this).data('action'); // Action with ID

                anneeModalLabel.text('<?php echo __("edit_academic_year_modal_title", "Modifier l’Année Académique"); ?>');
                anneeForm.attr('action', updateAction);

                anneeForm.find('input[name="id"]').val(id);
                anneeForm.find('#annee_libelle').val(libelle);
                anneeForm.find('#annee_date_debut').val(dateDebut);
                anneeForm.find('#annee_date_fin').val(dateFin);
                anneeForm.find('#annee_active').prop('checked', active == 1 || active === 'true' || active === true);

                anneeModal.modal('show');
            });

            // Reset form when modal is hidden (closed via X, cancel button, or ESC)
            anneeModal.on('hidden.bs.modal', function () {
                anneeForm.trigger('reset');
                anneeForm.find('input[name="id"]').val('');
                anneeForm.attr('action', originalAddAction); // Reset action to default (add)
                anneeModalLabel.text('<?php echo __("add_academic_year_modal_title", "Ajouter une Année Académique"); ?>');
            });
        });
    </script>
</body>
</html>
