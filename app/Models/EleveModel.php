<?php
namespace App\Models;
use App\Core\Model;

class EleveModel extends Model {
    // Basic CRUD for eleves table
    public function getAll($filters = [], $options = []) {
        $sql = "SELECT e.*,
                       aec.classe_id, c.nom as classe_nom,
                       aec.annee_academique_id, aa.libelle as annee_academique_libelle
                FROM eleves e
                LEFT JOIN affectations_eleves_classes aec ON e.id = aec.eleve_id
                     AND aec.annee_academique_id = (SELECT id FROM annees_academiques WHERE active = 1 LIMIT 1) -- Current active class
                LEFT JOIN classes c ON aec.classe_id = c.id
                LEFT JOIN annees_academiques aa ON aec.annee_academique_id = aa.id";
        $bindings = [];
        $whereClauses = [];

        if (!empty($filters['search'])) {
            $searchLike = '%' . $filters['search'] . '%';
            $whereClauses[] = "(e.nom_complet LIKE :search_nom_complet OR e.prenom LIKE :search_prenom OR e.nom_famille LIKE :search_nom_famille OR e.matricule LIKE :search_matricule OR e.email_eleve LIKE :search_email)";
            $bindings[':search_nom_complet'] = $searchLike;
            $bindings[':search_prenom'] = $searchLike;
            $bindings[':search_nom_famille'] = $searchLike;
            $bindings[':search_matricule'] = $searchLike;
            $bindings[':search_email'] = $searchLike;
        }
        if (!empty($filters['statut_eleve'])) {
            $whereClauses[] = "e.statut_eleve = :statut_eleve";
            $bindings[':statut_eleve'] = $filters['statut_eleve'];
        }
        if (!empty($filters['classe_id'])) {
             $whereClauses[] = "aec.classe_id = :classe_id_filter AND aec.annee_academique_id = (SELECT id FROM annees_academiques WHERE active = 1 LIMIT 1)";
             $bindings[':classe_id_filter'] = $filters['classe_id'];
        }

        if (!empty($whereClauses)) $sql .= " WHERE " . implode(' AND ', $whereClauses);

        $orderBy = $options['orderBy'] ?? 'e.nom_famille, e.prenom';
        $orderDir = $options['orderDir'] ?? 'ASC';
        $sql .= " ORDER BY " . $orderBy . " " . $orderDir;

        if (isset($options['limit'])) {
            $sql .= " LIMIT " . (int)$options['limit'] . (isset($options['offset']) ? " OFFSET " . (int)$options['offset'] : "");
        }
        $this->db->query($sql);
        foreach ($bindings as $key => $value) $this->db->bind($key, $value);
        return $this->db->resultSet();
    }

    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(DISTINCT e.id) as total
                FROM eleves e
                LEFT JOIN affectations_eleves_classes aec ON e.id = aec.eleve_id
                     AND aec.annee_academique_id = (SELECT id FROM annees_academiques WHERE active = 1 LIMIT 1)";
        $bindings = [];
        $whereClauses = [];

        if (!empty($filters['search'])) {
           $searchLike = '%' . $filters['search'] . '%';
           $whereClauses[] = "(e.nom_complet LIKE :search_nom_complet OR e.prenom LIKE :search_prenom OR e.nom_famille LIKE :search_nom_famille OR e.matricule LIKE :search_matricule OR e.email_eleve LIKE :search_email)";
           $bindings[':search_nom_complet'] = $searchLike; $bindings[':search_prenom'] = $searchLike; $bindings[':search_nom_famille'] = $searchLike; $bindings[':search_matricule'] = $searchLike; $bindings[':search_email'] = $searchLike;
        }
        if (!empty($filters['statut_eleve'])) { $whereClauses[] = "e.statut_eleve = :statut_eleve"; $bindings[':statut_eleve'] = $filters['statut_eleve'];}
        if (!empty($filters['classe_id'])) { $whereClauses[] = "aec.classe_id = :classe_id_filter AND aec.annee_academique_id = (SELECT id FROM annees_academiques WHERE active = 1 LIMIT 1)"; $bindings[':classe_id_filter'] = $filters['classe_id'];}

        if (!empty($whereClauses)) $sql .= " WHERE " . implode(' AND ', $whereClauses);
        $this->db->query($sql);
        foreach ($bindings as $key => $value) $this->db->bind($key, $value);
        $result = $this->db->single();
        return $result ? (int)$result->total : 0;
    }

    public function getById($id) {
        $this->db->query("SELECT * FROM eleves WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function findByMatricule($matricule) {
        $this->db->query("SELECT * FROM eleves WHERE matricule = :matricule");
        $this->db->bind(':matricule', $matricule);
        return $this->db->single();
    }

    public function generateMatricule($prefix = 'E') {
        $year = date('Y');
        // Get the count of students already registered this year with this prefix
        $this->db->query("SELECT COUNT(id) as count FROM eleves WHERE matricule LIKE :year_prefix");
        $this->db->bind(':year_prefix', $prefix . $year . '%');
        $result = $this->db->single();
        $nextSequentialId = ($result ? (int)$result->count : 0) + 1;

        $newMatricule = $prefix . $year . str_pad($nextSequentialId, 4, '0', STR_PAD_LEFT);

        // Check if somehow this generated matricule already exists (highly unlikely with year prefix and sequence)
        // This loop is a safeguard for extremely rare collisions or if sequence reset.
        $maxAttempts = 5;
        $attempt = 0;
        while ($this->findByMatricule($newMatricule) && $attempt < $maxAttempts) {
            $nextSequentialId++;
            $newMatricule = $prefix . $year . str_pad($nextSequentialId, 4, '0', STR_PAD_LEFT);
            $attempt++;
        }
        if ($this->findByMatricule($newMatricule)) {
            // If still colliding after attempts, fallback to something more unique or error
            // Consider logging this event as it's unusual.
            error_log("Matricule collision after $maxAttempts attempts for prefix $prefix, year $year. Falling back to time-based matricule.");
            return $prefix . $year . time() . rand(10,99); // Fallback, less pretty
        }
        return $newMatricule;
    }

    public function create($data) {
        // Ensure nom_complet is set
        $data['nom_complet'] = trim(($data['prenom'] ?? '') . ' ' . ($data['nom_famille'] ?? ''));

        if (empty($data['matricule'])) {
            $data['matricule'] = $this->generateMatricule();
        } elseif ($this->findByMatricule($data['matricule'])) {
            return 'duplicate_matricule';
        }

        if (!empty($data['email_eleve']) && $this->findByEmail($data['email_eleve'])) {
            return 'duplicate_email_eleve';
        }

        $sql = "INSERT INTO eleves (
                    matricule, prenom, nom_famille, nom_complet, date_naissance, lieu_naissance, sexe,
                    nationalite, adresse, telephone_mobile, email_eleve,
                    nom_responsable_legal1, telephone_responsable_legal1, email_responsable_legal1, profession_rl1,
                    nom_responsable_legal2, telephone_responsable_legal2, email_responsable_legal2, profession_rl2,
                    avatar_path, statut_eleve, date_inscription_initiale, notes_medicales,
                    utilisateur_id, date_creation, date_modification
                ) VALUES (
                    :matricule, :prenom, :nom_famille, :nom_complet, :date_naissance, :lieu_naissance, :sexe,
                    :nationalite, :adresse, :telephone_mobile, :email_eleve,
                    :nom_responsable_legal1, :telephone_responsable_legal1, :email_responsable_legal1, :profession_rl1,
                    :nom_responsable_legal2, :telephone_responsable_legal2, :email_responsable_legal2, :profession_rl2,
                    :avatar_path, :statut_eleve, :date_inscription_initiale, :notes_medicales,
                    :utilisateur_id, NOW(), NOW()
                )";
        $this->db->query($sql);

        $this->db->bind(':matricule', $data['matricule']);
        $this->db->bind(':prenom', $data['prenom']);
        $this->db->bind(':nom_famille', $data['nom_famille']);
        $this->db->bind(':nom_complet', $data['nom_complet']);
        $this->db->bind(':date_naissance', $data['date_naissance']);
        $this->db->bind(':lieu_naissance', $data['lieu_naissance'] ?? null);
        $this->db->bind(':sexe', $data['sexe']);
        $this->db->bind(':nationalite', $data['nationalite'] ?? null);
        $this->db->bind(':adresse', $data['adresse'] ?? null);
        $this->db->bind(':telephone_mobile', $data['telephone_mobile'] ?? null);
        $this->db->bind(':email_eleve', $data['email_eleve'] ?? null);
        $this->db->bind(':nom_responsable_legal1', $data['nom_responsable_legal1']);
        $this->db->bind(':telephone_responsable_legal1', $data['telephone_responsable_legal1']);
        $this->db->bind(':email_responsable_legal1', $data['email_responsable_legal1'] ?? null);
        $this->db->bind(':profession_rl1', $data['profession_rl1'] ?? null);
        $this->db->bind(':nom_responsable_legal2', $data['nom_responsable_legal2'] ?? null);
        $this->db->bind(':telephone_responsable_legal2', $data['telephone_responsable_legal2'] ?? null);
        $this->db->bind(':email_responsable_legal2', $data['email_responsable_legal2'] ?? null); // Added RL2 email
        $this->db->bind(':profession_rl2', $data['profession_rl2'] ?? null); // Added RL2 profession
        $this->db->bind(':avatar_path', $data['avatar_path'] ?? null);
        $this->db->bind(':statut_eleve', $data['statut_eleve'] ?? 'inscrit');
        $this->db->bind(':date_inscription_initiale', $data['date_inscription_initiale'] ?? date('Y-m-d'));
        $this->db->bind(':notes_medicales', $data['notes_medicales'] ?? null);
        $this->db->bind(':utilisateur_id', $data['utilisateur_id'] ?? null); // For potential linked user account

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
        } catch (\PDOException $e) {
            if ($e->getCode() == '23000') {
                 if (strpos($e->getMessage(), 'matricule') !== false) return 'duplicate_matricule';
                 if (strpos($e->getMessage(), 'email_eleve') !== false) return 'duplicate_email_eleve';
                 // Could add check for utilisateur_id if it has a unique constraint
            }
            error_log("Error creating student: " . $e->getMessage() . " SQL: " . $sql . " Data: " . print_r($data, true));
            return false;
        }
        return false;
    }

    public function findByEmail($email) {
         $this->db->query("SELECT * FROM eleves WHERE email_eleve = :email_eleve");
         $this->db->bind(':email_eleve', $email);
         return $this->db->single();
    }

    public function update($id, $data) {
        // Ensure nom_complet is updated if prenom or nom_famille changes
        if (isset($data['prenom']) || isset($data['nom_famille'])) {
            $currentData = $this->getById($id); // Fetch current data to get existing prenom/nom_famille if one is not in $data
            $prenom = $data['prenom'] ?? $currentData->prenom;
            $nom_famille = $data['nom_famille'] ?? $currentData->nom_famille;
            $data['nom_complet'] = trim($prenom . ' ' . $nom_famille);
        }


        if (!empty($data['matricule']) && ($existing = $this->findByMatricule($data['matricule'])) && $existing->id != $id) {
            return 'duplicate_matricule';
        }
        // Check for email duplication only if email is provided and is different from current email for that student
        if (!empty($data['email_eleve'])) {
            $currentEleve = $this->getById($id);
            if ($data['email_eleve'] !== $currentEleve->email_eleve) {
                if ($this->findByEmail($data['email_eleve'])) {
                    return 'duplicate_email_eleve';
                }
            }
        }


        // List of all updatable fields in the eleves table
        $allowedFields = [
            'prenom', 'nom_famille', 'nom_complet', 'date_naissance', 'lieu_naissance', 'sexe',
            'nationalite', 'adresse', 'telephone_mobile', 'email_eleve',
            'nom_responsable_legal1', 'telephone_responsable_legal1', 'email_responsable_legal1', 'profession_rl1',
            'nom_responsable_legal2', 'telephone_responsable_legal2', 'email_responsable_legal2', 'profession_rl2',
            'avatar_path', 'statut_eleve', 'date_inscription_initiale', 'notes_medicales',
            'utilisateur_id', 'matricule' // matricule is updatable if admin changes it, but usually fixed.
        ];

        $setParts = [];
        $bindings = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) { // Use array_key_exists to allow null values to be set explicitly
                $setParts[] = "$field = :$field";
                $bindings[":$field"] = $data[$field];
            }
        }

        if (empty($setParts)) {
            return true; // Nothing to update
        }

        $sql = "UPDATE eleves SET " . implode(', ', $setParts) . ", date_modification = NOW() WHERE id = :id";
        $this->db->query($sql);

        foreach($bindings as $key => $value) {
            $this->db->bind($key, $value);
        }

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            if ($e->getCode() == '23000') {
                 if (strpos($e->getMessage(), 'matricule') !== false) return 'duplicate_matricule';
                 if (strpos($e->getMessage(), 'email_eleve') !== false) return 'duplicate_email_eleve';
            }
            error_log("Error updating student " . $id . ": " . $e->getMessage() . " SQL: " . $sql . " Data: " . print_r($data, true));
            return false;
        }
    }

    public function delete($id) {
        // Consider checking dependencies or using soft delete (e.g., setting statut_eleve to 'radie')
        // For now, a hard delete:
        try {
            $this->db->query("DELETE FROM eleves WHERE id = :id");
            $this->db->bind(':id', $id);
            return $this->db->execute();
        } catch (\PDOException $e) {
            // Handle foreign key constraint violation (e.g., if student has related records)
            error_log("Error deleting student " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    public function getStatutsEleve() {
         return ['preinscrit', 'inscrit', 'actif', 'suspendu', 'transfere', 'radie', 'ancien'];
    }
}
?>
