<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class UserRoleModel extends Model {

    /**
     * Assigns a role to a user, optionally for a specific academic year.
     * Uses INSERT IGNORE to prevent errors if the assignment already exists.
     */
    public function assignRoleToUser($userId, $roleId, $anneeAcademiqueId = null) {
        $this->db->query("INSERT IGNORE INTO user_roles (utilisateur_id, role_id, annee_academique_id) VALUES (:user_id, :role_id, :annee_id)");
        $this->db->bind(':user_id', $userId, PDO::PARAM_INT);
        $this->db->bind(':role_id', $roleId, PDO::PARAM_INT);
        $this->db->bind(':annee_id', $anneeAcademiqueId, $anneeAcademiqueId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("UserRoleModel::assignRoleToUser Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Revokes a specific role from a user, optionally for a specific academic year.
     */
    public function revokeRoleFromUser($userId, $roleId, $anneeAcademiqueId = null) {
        $sql = "DELETE FROM user_roles WHERE utilisateur_id = :user_id AND role_id = :role_id";
        if ($anneeAcademiqueId === null) {
            $sql .= " AND annee_academique_id IS NULL";
        } else {
            $sql .= " AND annee_academique_id = :annee_id";
            $this->db->bind(':annee_id', $anneeAcademiqueId, PDO::PARAM_INT);
        }
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId, PDO::PARAM_INT);
        $this->db->bind(':role_id', $roleId, PDO::PARAM_INT);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("UserRoleModel::revokeRoleFromUser Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Revokes all roles for a user, either globally or for a specific academic year.
     * If $anneeAcademiqueId is null, revokes only global roles (where annee_academique_id IS NULL).
     * If $anneeAcademiqueId is provided, revokes only roles for that specific year.
     * To revoke ALL roles for a user across ALL years, this method would need modification or multiple calls.
     */
    public function revokeAllRolesForUserInContext($userId, $anneeAcademiqueId = null) {
        $sql = "DELETE FROM user_roles WHERE utilisateur_id = :user_id";
        if ($anneeAcademiqueId === null) {
            $sql .= " AND annee_academique_id IS NULL"; // Only global roles
        } else {
            $sql .= " AND annee_academique_id = :annee_id"; // Only roles for this specific year
            $this->db->bind(':annee_id', $anneeAcademiqueId, PDO::PARAM_INT);
        }
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId, PDO::PARAM_INT);

        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("UserRoleModel::revokeAllRolesForUserInContext Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets all roles for a user.
     * If $anneeAcademiqueId is provided, it fetches roles for that specific year AND global roles.
     * If $anneeAcademiqueId is null (e.g. for login or non-academic context), it fetches ONLY global roles.
     * If $anneeAcademiqueId is special value (e.g. 'all_contexts'), it could fetch all roles regardless of year.
     */
    public function getRolesForUser($userId, $anneeAcademiqueId = null, $includeGlobalWithYear = true) {
        $sql = "SELECT r.*, ur.annee_academique_id FROM roles r
                JOIN user_roles ur ON r.id = ur.role_id
                WHERE ur.utilisateur_id = :user_id";

        $params = [':user_id' => $userId];

        if ($anneeAcademiqueId !== null) {
            if ($includeGlobalWithYear) {
                $sql .= " AND (ur.annee_academique_id = :annee_id OR ur.annee_academique_id IS NULL)";
                $params[':annee_id'] = $anneeAcademiqueId;
            } else {
                $sql .= " AND ur.annee_academique_id = :annee_id";
                $params[':annee_id'] = $anneeAcademiqueId;
            }
        } else {
            // If no specific year, fetch only global roles by default
            $sql .= " AND ur.annee_academique_id IS NULL";
        }
        $sql .= " ORDER BY r.nom";

        $this->db->query($sql);
        foreach($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->resultSet();
    }

    /**
     * Gets just the names of roles for a user in a given context.
     */
    public function getRoleNamesForUser($userId, $anneeAcademiqueId = null, $includeGlobalWithYear = true) {
        $roles = $this->getRolesForUser($userId, $anneeAcademiqueId, $includeGlobalWithYear);
        return array_map(function($role) { return $role->nom; }, $roles);
    }


    /**
     * Checks if a user has a specific role by name in a given academic year context (or globally).
     */
    public function userHasRole($userId, $roleName, $anneeAcademiqueId = null, $includeGlobalWithYear = true) {
        $roles = $this->getRoleNamesForUser($userId, $anneeAcademiqueId, $includeGlobalWithYear);
        return in_array($roleName, $roles);
    }

    /**
     * Synchronizes roles for a user in a specific context (year or global).
     * Roles not in $roleIds will be revoked from the context.
     * Roles in $roleIds will be added to the context if not already present.
     * @param int $userId
     * @param array $roleIds Array of role IDs to be assigned in this context.
     * @param int|null $anneeAcademiqueId The academic year context. NULL for global context.
     * @return bool True on overall success.
     */
    public function syncUserRoles($userId, $roleIds = [], $anneeAcademiqueId = null) {
        // Fetch current roles specifically for the given context (not including global if year is specified, for precise sync)
        $currentRoleObjectsInContext = $this->getRolesForUser($userId, $anneeAcademiqueId, false); // false for $includeGlobalWithYear
        $currentRoleIdsInContext = array_map(function($r) { return $r->id; }, $currentRoleObjectsInContext);

        // Revoke roles that are currently in context but not in the new $roleIds list
        $rolesToRevoke = array_diff($currentRoleIdsInContext, $roleIds);
        foreach ($rolesToRevoke as $roleIdToRevoke) {
            $this->revokeRoleFromUser($userId, $roleIdToRevoke, $anneeAcademiqueId);
        }

        // Assign new roles that are in $roleIds but not currently in context
        $rolesToAssign = array_diff($roleIds, $currentRoleIdsInContext);
        foreach ($rolesToAssign as $roleIdToAssign) {
            $this->assignRoleToUser($userId, $roleIdToAssign, $anneeAcademiqueId);
        }
        return true; // Consider adding more robust error checking for each step
    }
}
?>
