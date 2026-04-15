<?php

namespace App\Livewire;

use App\Models\Role;
use Filament\Notifications\Notification;
use Livewire\Component;

class RoleManager extends Component
{
    public ?int $selectedRoleId = null;
    public array $permissions = [];

    // Add Role modal
    public bool $showAddModal = false;
    public string $newRoleName = '';
    public array $newPermissions = [];

    // Edit name
    public bool $editingName = false;
    public string $editName = '';

    public function updatedSelectedRoleId($value): void
    {
        if ($value) {
            $role = Role::find($value);
            $this->permissions = $role?->permissions ?? [];
        } else {
            $this->permissions = [];
        }
        $this->editingName = false;
    }

    public function openAddModal(): void
    {
        $this->newRoleName = '';
        $this->newPermissions = [];
        $this->showAddModal = true;
    }

    public function addRole(): void
    {
        $this->validate([
            'newRoleName' => 'required|min:1|unique:roles,name',
        ], [
            'newRoleName.unique' => 'A role with this name already exists.',
        ]);

        $role = Role::create([
            'name' => $this->newRoleName,
            'permissions' => $this->newPermissions,
        ]);

        $this->showAddModal = false;
        $this->selectedRoleId = $role->id;
        $this->permissions = $role->permissions ?? [];
        $this->newRoleName = '';
        $this->newPermissions = [];

        Notification::make()
            ->success()
            ->title("Role \"{$role->name}\" created")
            ->send();
    }

    public function savePermissions(): void
    {
        if (! $this->selectedRoleId) {
            return;
        }

        $role = Role::find($this->selectedRoleId);

        if (! $role) {
            return;
        }

        $role->update(['permissions' => $this->permissions]);

        Notification::make()
            ->success()
            ->title("Permissions updated for \"{$role->name}\"")
            ->send();
    }

    public function startEditName(): void
    {
        if (! $this->selectedRoleId) {
            return;
        }

        $role = Role::find($this->selectedRoleId);
        $this->editName = $role?->name ?? '';
        $this->editingName = true;
    }

    public function saveName(): void
    {
        $this->validate([
            'editName' => 'required|min:1|unique:roles,name,' . $this->selectedRoleId,
        ]);

        $role = Role::find($this->selectedRoleId);

        if ($role) {
            $role->update(['name' => $this->editName]);
        }

        $this->editingName = false;

        Notification::make()
            ->success()
            ->title('Role renamed')
            ->send();
    }

    public function cancelEditName(): void
    {
        $this->editingName = false;
    }

    public function deleteRole(): void
    {
        if (! $this->selectedRoleId) {
            return;
        }

        $role = Role::find($this->selectedRoleId);
        $name = $role?->name;
        $role?->delete();

        $this->selectedRoleId = null;
        $this->permissions = [];

        Notification::make()
            ->success()
            ->title("Role \"{$name}\" deleted")
            ->send();
    }

    public function render()
    {
        return view('livewire.role-manager', [
            'roles' => Role::orderBy('name')->get(),
            'resourceOptions' => Role::resourceOptions(),
            'selectedRole' => $this->selectedRoleId ? Role::find($this->selectedRoleId) : null,
        ]);
    }
}
