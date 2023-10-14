<?php

namespace App\Observers;

use App\Models\Role;
use App\Models\Permission;

class RoleObserver
{
    private function updatePermissions(Role $role): void
    {
        $permission_ids = $role->permission_ids;

        if (empty($permission_ids)) {
            return;
        }

        $role->permissions()->detach();

        foreach ($permission_ids as $id) {
            $role->givePermissionTo(Permission::find($id)->value('name'));
        }
    }

    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        $this->updatePermissions($role);
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        $this->updatePermissions($role);
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        $role->users()->detach();
        $role->permissions()->detach();
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        //
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        $role->users()->detach();
        $role->permissions()->detach();
    }
}
