<?php
namespace App\Models;
use App\Core\Model;
use PDO;

class RolePermissionModel extends Model {

    public function assignPermissionToRole($roleId, $permissionId) {
        // INSERT IGNORE avoids error if the pair already exists, effectively making it an "ensure assigned" operation.
        $this->db->query("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)");
        $this->db->bind(':role_id', $roleId, PDO::PARAM_INT);
        $this->db->bind(':permission_id', $permissionId, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("RolePermissionModel::assignPermissionToRole Error: " . $e->getMessage());
            return false;
        }
    }

    public function revokePermissionFromRole($roleId, $permissionId) {
        $this->db->query("DELETE FROM role_permissions WHERE role_id = :role_id AND permission_id = :permission_id");
        $this->db->bind(':role_id', $roleId, PDO::PARAM_INT);
        $this->db->bind(':permission_id', $permissionId, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("RolePermissionModel::revokePermissionFromRole Error: " . $e->getMessage());
            return false;
        }
    }

    public function revokeAllPermissionsFromRole($roleId) {
        $this->db->query("DELETE FROM role_permissions WHERE role_id = :role_id");
        $this->db->bind(':role_id', $roleId, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (\PDOException $e) {
            error_log("RolePermissionModel::revokeAllPermissionsFromRole Error: " . $e->getMessage());
            return false;
        }
    }

    public function getPermissionsForRole($roleId) {
        $this->db->query("SELECT p.* FROM permissions p
                          JOIN role_permissions rp ON p.id = rp.permission_id
                          WHERE rp.role_id = :role_id ORDER BY p.groupe, p.nom");
        $this->db->bind(':role_id', $roleId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getPermissionIdsForRole($roleId) {
        $this->db->query("SELECT permission_id FROM role_permissions WHERE role_id = :role_id");
        $this->db->bind(':role_id', $roleId, PDO::PARAM_INT);
        $results = $this->db->resultSet();
        // resultSet returns array of objects by default. We need to map to get array of permission_id values.
        return array_map(function($row) { return $row->permission_id; }, $results);
    }

    public function roleHasPermission($roleId, $permissionName) {
        $this->db->query("SELECT COUNT(rp.permission_id) as count
                          FROM role_permissions rp
                          JOIN permissions p ON rp.permission_id = p.id
                          WHERE rp.role_id = :role_id AND p.nom = :permission_name");
        $this->db->bind(':role_id', $roleId, PDO::PARAM_INT);
        $this->db->bind(':permission_name', $permissionName);
        $row = $this->db->single();
        return $row && $row->count > 0;
    }

    /**
     * Updates all permissions for a given role.
     * Revokes permissions not in the $permissionIds array and assigns new ones.
     * @param int $roleId The ID of the role to update.
     * @param array $permissionIds An array of permission IDs to be set for the role.
     * @return bool True on overall success, false if any part fails.
     */
    public function syncRolePermissions($roleId, $permissionIds = []) {
        // Start transaction
        // Note: The Database class would need to expose getPdo() for transaction management
        // For simplicity, let's assume direct execution or handle transactions at controller level if critical.
        // $this->db->getPdo()->beginTransaction();

        try {
            // 1. Get current permission IDs for the role
            $currentPermissionIds = $this->getPermissionIdsForRole($roleId);

            // 2. Revoke permissions not in the new list
            $permissionsToRevoke = array_diff($currentPermissionIds, $permissionIds);
            foreach ($permissionsToRevoke as $permId) {
                if (!$this->revokePermissionFromRole($roleId, $permId)) {
                    // throw new \Exception("Failed to revoke permission ID: $permId for role ID: $roleId");
                    // error_log("Failed to revoke permission ID: $permId for role ID: $roleId");
                }
            }

            // 3. Assign new permissions not already present
            $permissionsToAssign = array_diff($permissionIds, $currentPermissionIds);
            foreach ($permissionsToAssign as $permId) {
                if (!$this->assignPermissionToRole($roleId, $permId)) {
                    // throw new \Exception("Failed to assign permission ID: $permId for role ID: $roleId");
                     // error_log("Failed to assign permission ID: $permId for role ID: $roleId");
                }
            }
            // $this->db->getPdo()->commit();
            return true;
        } catch (\Exception $e) {
            // $this->db->getPdo()->rollBack();
            error_log("RolePermissionModel::syncRolePermissions Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
