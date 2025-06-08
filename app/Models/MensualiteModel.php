<?php
namespace App\Models;
use App\Core\Model;

class MensualiteModel extends Model {

    public function getMensualitesForStudentYear($eleveId, $anneeAcademiqueId, $filters = []) {
        $sql = "SELECT m.*,
                       u_creation.nom_complet as cree_par_nom,
                       u_paiement.nom_complet as dernier_paiement_par_nom
                FROM mensualites m
                LEFT JOIN utilisateurs u_creation ON m.cree_par_utilisateur_id = u_creation.id
                LEFT JOIN utilisateurs u_paiement ON m.dernier_paiement_par_utilisateur_id = u_paiement.id
                WHERE m.eleve_id = :eleve_id AND m.annee_academique_id = :annee_id";

        $bindings = [':eleve_id' => $eleveId, ':annee_id' => $anneeAcademiqueId];

        if (!empty($filters['statut_paiement'])) {
            $sql .= " AND m.statut_paiement = :statut_paiement";
            $bindings[':statut_paiement'] = $filters['statut_paiement'];
        }
        if (!empty($filters['mois'])) {
            $sql .= " AND m.mois = :mois";
            $bindings[':mois'] = $filters['mois'];
        }
        if (!empty($filters['annee_concernee'])) {
            $sql .= " AND m.annee_concernee = :annee_concernee";
            $bindings[':annee_concernee'] = $filters['annee_concernee'];
        }

        // Standard month order for French academic year context
        $sql .= " ORDER BY m.annee_concernee, FIELD(m.mois, 'Octobre', 'Novembre', 'Décembre', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre')";

        $this->db->query($sql);
        foreach($bindings as $key => $value) $this->db->bind($key, $value);
        return $this->db->resultSet();
    }

    public function getById($id){
        $this->db->query("SELECT * FROM mensualites WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function createPaymentEntry($data){
        // Check for existing entry for this student, year, month, annee_concernee to prevent duplicates.
        $this->db->query("SELECT id FROM mensualites
                          WHERE eleve_id = :eleve_id
                          AND annee_academique_id = :annee_academique_id
                          AND mois = :mois
                          AND annee_concernee = :annee_concernee");
        $this->db->bind(':eleve_id', $data['eleve_id']);
        $this->db->bind(':annee_academique_id', $data['annee_academique_id']);
        $this->db->bind(':mois', $data['mois']);
        $this->db->bind(':annee_concernee', $data['annee_concernee']);
        if ($this->db->single()) {
            return 'duplicate_entry';
        }

        $sql = "INSERT INTO mensualites (eleve_id, annee_academique_id, mois, annee_concernee, montant_attendu, montant_reduction_applique, montant_a_payer, montant_paye, date_echeance, statut_paiement, commentaire, cree_par_utilisateur_id, date_creation)
                VALUES (:eleve_id, :annee_academique_id, :mois, :annee_concernee, :montant_attendu, :montant_reduction_applique, :montant_a_payer, :montant_paye, :date_echeance, :statut_paiement, :commentaire, :cree_par_utilisateur_id, NOW())";
        $this->db->query($sql);
        $this->db->bind(':eleve_id', $data['eleve_id']);
        $this->db->bind(':annee_academique_id', $data['annee_academique_id']);
        $this->db->bind(':mois', $data['mois']);
        $this->db->bind(':annee_concernee', $data['annee_concernee']);
        $this->db->bind(':montant_attendu', $data['montant_attendu']);
        $this->db->bind(':montant_reduction_applique', $data['montant_reduction_applique'] ?? 0);
        $this->db->bind(':montant_a_payer', $data['montant_a_payer'] ?? $data['montant_attendu'] - ($data['montant_reduction_applique'] ?? 0));
        $this->db->bind(':montant_paye', $data['montant_paye'] ?? 0);
        $this->db->bind(':date_echeance', $data['date_echeance'] ?? null);
        $this->db->bind(':statut_paiement', $data['statut_paiement'] ?? 'impaye'); // e.g., impaye, paye_partiel, paye_total, exonere
        $this->db->bind(':commentaire', $data['commentaire'] ?? null);
        $this->db->bind(':cree_par_utilisateur_id', $data['cree_par_utilisateur_id'] ?? null);

        try {
            if ($this->db->execute()) return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error creating payment entry: " . $e->getMessage());
            return false;
        }
        return false;
    }

    public function recordPayment($mensualiteId, $montantPayeCeVersement, $datePaiement, $methodePaiement, $recuNumero = null, $payeParUtilisateurId = null){
        $entry = $this->getById($mensualiteId);
        if (!$entry) return 'not_found';

        $nouveauMontantPaye = ($entry->montant_paye ?? 0) + $montantPayeCeVersement;
        $montantAPayer = $entry->montant_a_payer;

        $newStatus = $entry->statut_paiement;
        if ($nouveauMontantPaye >= $montantAPayer) {
            $newStatus = 'paye_total';
            $nouveauMontantPaye = $montantAPayer; // Cap at amount due to avoid overpayment issues in this field
        } elseif ($nouveauMontantPaye > 0) {
            $newStatus = 'paye_partiel';
        } else {
            $newStatus = 'impaye'; // Should not happen if $montantPayeCeVersement is positive
        }

        $sql = "UPDATE mensualites SET
                    montant_paye = :nouveau_montant_paye,
                    date_dernier_paiement = :date_paiement,
                    methode_dernier_paiement = :methode_paiement,
                    numero_recu_dernier_paiement = :recu_numero,
                    statut_paiement = :new_status,
                    dernier_paiement_par_utilisateur_id = :paye_par_utilisateur_id,
                    date_modification = NOW()
                WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $mensualiteId);
        $this->db->bind(':nouveau_montant_paye', $nouveauMontantPaye);
        $this->db->bind(':date_paiement', $datePaiement);
        $this->db->bind(':methode_paiement', $methodePaiement);
        $this->db->bind(':recu_numero', $recuNumero);
        $this->db->bind(':new_status', $newStatus);
        $this->db->bind(':paye_par_utilisateur_id', $payeParUtilisateurId);

        return $this->db->execute();
    }

    public function updateStatus($mensualiteId, $newStatus, $commentaire = null){
        $sql = "UPDATE mensualites SET statut_paiement = :new_status, date_modification = NOW()";
        if ($commentaire !== null) {
            $sql .= ", commentaire = :commentaire";
        }
        $sql .= " WHERE id = :id";

        $this->db->query($sql);
        $this->db->bind(':id', $mensualiteId);
        $this->db->bind(':new_status', $newStatus);
        if ($commentaire !== null) {
            $this->db->bind(':commentaire', $commentaire);
        }
        return $this->db->execute();
    }

    public function calculateTotalDueForStudentYear($eleveId, $anneeAcademiqueId) {
        $this->db->query("SELECT SUM(montant_a_payer) as total_due FROM mensualites
                          WHERE eleve_id = :eleve_id AND annee_academique_id = :annee_id");
        $this->db->bind(':eleve_id', $eleveId);
        $this->db->bind(':annee_id', $anneeAcademiqueId);
        $result = $this->db->single();
        return $result ? (float)$result->total_due : 0;
    }

    public function calculateTotalPaidForStudentYear($eleveId, $anneeAcademiqueId) {
        $this->db->query("SELECT SUM(montant_paye) as total_paid FROM mensualites
                          WHERE eleve_id = :eleve_id AND annee_academique_id = :annee_id");
        $this->db->bind(':eleve_id', $eleveId);
        $this->db->bind(':annee_id', $anneeAcademiqueId);
        $result = $this->db->single();
        return $result ? (float)$result->total_paid : 0;
    }

    /**
     * Conceptual: Checks if all payments up to a certain month are fully paid.
     * This requires a defined order of months.
     */
    public function arePaymentsUpToDate($eleveId, $anneeAcademiqueId, $currentMonthName, $academicMonthsOrder) {
       // $academicMonthsOrder: array like ['Octobre', 'Novembre', ..., 'Juillet']
       // $currentMonthName: The name of the current school month (e.g., "Décembre")

       if (empty($academicMonthsOrder) || !is_array($academicMonthsOrder)) {
            error_log("arePaymentsUpToDate: academicMonthsOrder is empty or not an array.");
            return false; // Cannot determine order
       }

       $this->db->query("SELECT mois, annee_concernee, statut_paiement FROM mensualites
                         WHERE eleve_id = :eleve_id AND annee_academique_id = :annee_id
                         ORDER BY annee_concernee ASC, FIELD(mois, '" . implode("','", array_map([$this->db->getPdo(), 'quote'], $academicMonthsOrder)) . "') ASC");
       $this->db->bind(':eleve_id', $eleveId);
       $this->db->bind(':annee_id', $anneeAcademiqueId);
       $allPayments = $this->db->resultSet();

       if (empty($allPayments)) return true;

       $currentMonthIndex = array_search($currentMonthName, $academicMonthsOrder);
       if ($currentMonthIndex === false) {
            error_log("arePaymentsUpToDate: currentMonthName '$currentMonthName' not found in academicMonthsOrder.");
            return false; // Current month not in academic calendar
       }

       foreach ($allPayments as $payment) {
           $paymentMonthIndex = array_search($payment->mois, $academicMonthsOrder);
           if ($paymentMonthIndex === false) continue;

           if ($paymentMonthIndex < $currentMonthIndex) {
               if (!in_array($payment->statut_paiement, ['paye_total', 'exonere'])) {
                   return false;
               }
           }
       }
       return true;
    }

    public function areAllDueInstallmentsPaid($eleveId, $anneeAcademiqueId) {
        $today = date('Y-m-d');
        $this->db->query("SELECT COUNT(id) as unpaid_due_count FROM mensualites
                          WHERE eleve_id = :eleve_id
                          AND annee_academique_id = :annee_id
                          AND date_echeance IS NOT NULL
                          AND date_echeance < :today
                          AND statut_paiement NOT IN ('paye_total', 'exonere')");
        $this->db->bind(':eleve_id', $eleveId);
        $this->db->bind(':annee_id', $anneeAcademiqueId);
        $this->db->bind(':today', $today);
        $result = $this->db->single();
        return ($result && $result->unpaid_due_count == 0);
    }

    public function generateExpectedPayments($eleveId, $anneeAcademiqueId, $montantMensuelBrut, $moisScolaires, $anneeConcerneeStart, $forceRegenerate = false, $defaultDueDateDay = 5) {
        if (!$eleveId || !$anneeAcademiqueId || $montantMensuelBrut <= 0 || empty($moisScolaires) || !is_array($moisScolaires)) {
            return ['success' => false, 'message' => 'Invalid parameters for generating payments.'];
        }

        $eleveReductionModel = new EleveReductionAppliqueeModel();
        $appliedReductions = $eleveReductionModel->getAppliedReductionsForStudentYear($eleveId, $anneeAcademiqueId);

        // Calculate total % and fixed reductions applicable per installment.
        // This assumes reductions are applied monthly. More complex logic (e.g., one-time reduction) would need adjustment.
        $totalMonthlyReductionPercentage = 0;
        $totalMonthlyReductionFixe = 0; // This is total fixed reduction to be distributed or applied monthly

        // Example: Distribute total fixed annual reduction over number of months
        // Or, if a reduction type is specifically "monthly fixed", it applies each month.
        // For this example, let's assume fixed reductions in `eleve_reductions_appliquees` are annual totals to be distributed.
        // And percentage reductions are applied to the monthly gross.
        $annualFixedReductionSum = 0;
        foreach($appliedReductions as $ar) {
            if(!empty($ar->montant_fixe_reduction) && $ar->montant_fixe_reduction > 0) { // This is the type's fixed amount
                 // If montant_calcule_reduction already stores the specific amount for this student for the year
                $annualFixedReductionSum += (float)($ar->montant_calcule_reduction ?? $ar->montant_fixe_reduction);
            }
            if(!empty($ar->pourcentage_reduction) && $ar->pourcentage_reduction > 0) {
                $totalMonthlyReductionPercentage += (float)$ar->pourcentage_reduction / 100; // Convert to decimal for calculation
            }
        }
        $numInstallments = count($moisScolaires);
        $distributedFixedReductionPerMonth = ($numInstallments > 0) ? $annualFixedReductionSum / $numInstallments : 0;

        $generatedCount = 0;
        $skippedCount = 0;
        $currentCalendarYear = $anneeConcerneeStart;
        $monthNumberMapping = [
            'Janvier'=>1, 'Février'=>2, 'Mars'=>3, 'Avril'=>4, 'Mai'=>5, 'Juin'=>6,
            'Juillet'=>7, 'Août'=>8, 'Septembre'=>9, 'Octobre'=>10, 'Novembre'=>11, 'Décembre'=>12
        ];

        foreach ($moisScolaires as $index => $moisNom) {
            if ($index > 0 && $monthNumberMapping[$moisNom] < $monthNumberMapping[$moisScolaires[$index-1]]) {
                 $currentCalendarYear = $anneeConcerneeStart + 1;
            }

            $this->db->query("SELECT id FROM mensualites WHERE eleve_id = :eid AND annee_academique_id = :aid AND mois = :m AND annee_concernee = :acy");
            $this->db->bind(':eid', $eleveId);
            $this->db->bind(':aid', $anneeAcademiqueId);
            $this->db->bind(':m', $moisNom);
            $this->db->bind(':acy', $currentCalendarYear);

            if ($this->db->single() && !$forceRegenerate) {
                $skippedCount++;
                continue;
            }

            $reductionPourcentageSurCeMois = $montantMensuelBrut * $totalMonthlyReductionPercentage;
            $montantReductionApplique = $reductionPourcentageSurCeMois + $distributedFixedReductionPerMonth;
            $montantReductionApplique = min($montantReductionApplique, $montantMensuelBrut);

            $montantAPayer = $montantMensuelBrut - $montantReductionApplique;

            // Calculate due date (e.g., 5th of the month)
            $dueDate = date('Y-m-d', mktime(0, 0, 0, $monthNumberMapping[$moisNom], $defaultDueDateDay, $currentCalendarYear));

            $data = [
                'eleve_id' => $eleveId,
                'annee_academique_id' => $anneeAcademiqueId,
                'mois' => $moisNom,
                'annee_concernee' => $currentCalendarYear,
                'montant_attendu' => $montantMensuelBrut,
                'montant_reduction_applique' => $montantReductionApplique,
                'montant_a_payer' => $montantAPayer, // Calculated net amount
                'date_echeance' => $dueDate,
                'statut_paiement' => 'impaye',
                'cree_par_utilisateur_id' => \App\Core\Auth::getCurrentUserId() ?? null // Make sure Auth is available
            ];
            // Use the existing createPaymentEntry, ensuring it does not try to re-check for duplicates if we are here.
            // Modify createPaymentEntry to accept a flag to skip duplicate check if called from here.
            // For now, assume createPaymentEntry will handle its own checks or this logic path is for new entries.

            // If forcing regenerate, we might need to delete existing first, then create.
            // Or, update existing. For simplicity, this example focuses on generation.
            // If an entry was skipped due to !$forceRegenerate, it's not touched.
            // If $forceRegenerate is true, this part implies an update or delete+insert if found.
            // The current check `if ($this->db->single() && !$forceRegenerate)` handles skipping.
            // If $forceRegenerate is true, it will proceed to try and insert.
            // `createPaymentEntry` must handle the "duplicate_entry" case gracefully or allow overwrite.

            $creationResult = $this->createPaymentEntry($data); // createPaymentEntry should handle INSERT
            if (is_numeric($creationResult)) { // ID returned
                $generatedCount++;
            } elseif ($creationResult === 'duplicate_entry' && $forceRegenerate) {
                // Optionally, update the existing entry if forceRegenerate is true
                // This would require fetching the ID of the duplicate and calling an update method.
                // For now, we count it as skipped if duplicate and not overwriting.
                $skippedCount++;
            }
        }
        return ['success' => true, 'generated' => $generatedCount, 'skipped' => $skippedCount, 'message' => "Generated: $generatedCount, Skipped: $skippedCount"];
    }

    public function getStatutsPaiement() {
        return ['impaye', 'paye_partiel', 'paye_total', 'exonere', 'annule'];
    }

    public function getMoisScolaires() { // Could be from config or AnneeAcademique settings
        return ['Octobre', 'Novembre', 'Décembre', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre'];
    }
}
?>
