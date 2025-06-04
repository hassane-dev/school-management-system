<?php

class RolePermission_model extends Model {

    public function __construct() {
        parent::__construct();
        // This model primarily works with the 'role_permissions' junction table,
        // but also interacts with 'roles' and 'permissions' tables for some operations.
        $this->table = 'role_permissions'; // Default table for simple operations if any
    }

    /**
     * Assign a permission to a role.
     * @param int $roleId
     * @param int $permissionId
     * @return bool True on success, false on failure or if already exists.
     */
    public function assignPermissionToRole($roleId, $permissionId) {
        if (empty($roleId) || empty($permissionId)) {
            return false;
        }
        // Check if the assignment already exists to avoid duplicate entry errors
        $sql_check = "SELECT COUNT(*) as count FROM role_permissions WHERE role_id = :role_id AND permission_id = :permission_id";
        $stmt_check = $this->executeQuery($sql_check, ['role_id' => $roleId, 'permission_id' => $permissionId]);
        $result = $stmt_check->fetch();

        if ($result && $result->count > 0) {
            return true; // Already assigned, consider it a success
        }

        $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
        $stmt = $this->executeQuery($sql, ['role_id' => $roleId, 'permission_id' => $permissionId]);
        return $stmt !== false; // $stmt will be PDOStatement on success, false on failure from executeQuery
    }

    /**
     * Remove a permission from a role.
     * @param int $roleId
     * @param int $permissionId
     * @return bool True on success, false on failure.
     */
    public function removePermissionFromRole($roleId, $permissionId) {
        if (empty($roleId) || empty($permissionId)) {
            return false;
        }
        $sql = "DELETE FROM role_permissions WHERE role_id = :role_id AND permission_id = :permission_id";
        $stmt = $this->executeQuery($sql, ['role_id' => $roleId, 'permission_id' => $permissionId]);
        return $stmt ? $stmt->rowCount() > 0 : false;
    }

    /**
     * Get all permissions (names or objects) for a given role ID.
     * @param int $roleId
     * @param bool $fetchObjects If true, fetches full permission objects, otherwise just names.
     * @return array Array of permission names or objects, or empty array.
     */
    public function getPermissionsForRole($roleId, $fetchObjects = false) {
        if (empty($roleId)) {
            return [];
        }
        if ($fetchObjects) {
            $sql = "SELECT p.* FROM permissions p
                    INNER JOIN role_permissions rp ON p.id = rp.permission_id
                    WHERE rp.role_id = :role_id";
        } else {
            $sql = "SELECT p.nom FROM permissions p
                    INNER JOIN role_permissions rp ON p.id = rp.permission_id
                    WHERE rp.role_id = :role_id";
        }

        $stmt = $this->executeQuery($sql, ['role_id' => $roleId]);

        if ($stmt) {
            return $fetchObjects ? $stmt->fetchAll(PDO::FETCH_OBJ) : $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        }
        return [];
    }

    /**
     * Checks if a role has a specific permission.
     * @param int $roleId
     * @param mixed $permissionIdentifier Permission name (string) or permission ID (int).
     * @return bool True if the role has the permission, false otherwise.
     */
    public function hasPermission($roleId, $permissionIdentifier) {
        if (empty($roleId) || empty($permissionIdentifier)) {
            return false;
        }

        $params = ['role_id' => $roleId];
        if (is_numeric($permissionIdentifier)) {
            $sql = "SELECT COUNT(*) as count FROM role_permissions
                    WHERE role_id = :role_id AND permission_id = :permission_id";
            $params['permission_id'] = $permissionIdentifier;
        } else {
            // Assumes permissionIdentifier is a permission name
            $sql = "SELECT COUNT(*) as count FROM role_permissions rp
                    INNER JOIN permissions p ON rp.permission_id = p.id
                    WHERE rp.role_id = :role_id AND p.nom = :permission_nom";
            $params['permission_nom'] = $permissionIdentifier;
        }

        $stmt = $this->executeQuery($sql, $params);
        $result = $stmt ? $stmt->fetch() : null;

        return $result && $result->count > 0;
    }

    /**
     * Get all role IDs associated with a specific permission ID.
     * Useful for finding all roles that have a certain capability.
     * @param int $permissionId
     * @return array Array of role IDs.
     */
    public function getRolesForPermission($permissionId) {
        if (empty($permissionId)) {
            return [];
        }
        $sql = "SELECT role_id FROM role_permissions WHERE permission_id = :permission_id";
        $stmt = $this->executeQuery($sql, ['permission_id' => $permissionId]);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];
    }
}
?>
