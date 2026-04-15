<div>
    {{-- Role Selector --}}
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
        <div style="flex: 1;">
            <select wire:model.live="selectedRoleId"
                    style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; color: #111827; background-color: white; outline: none; font-family: inherit; cursor: pointer;"
                    onfocus="this.style.borderColor='#ed2537'; this.style.boxShadow='0 0 0 1px #ed2537'"
                    onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'">
                <option value="">Select a role...</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="button"
                wire:click="openAddModal"
                style="display: inline-flex; align-items: center; gap: 0.375rem; background-color: #ed2537; color: white; font-weight: 600; font-size: 0.875rem; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; cursor: pointer; white-space: nowrap; font-family: inherit;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem;">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
            </svg>
            Add Role
        </button>
    </div>

    {{-- Selected Role Permissions --}}
    @if($selectedRole)
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem;">
            {{-- Role Header --}}
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
                @if($editingName)
                    <form wire:submit="saveName" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                        <input type="text"
                               wire:model="editName"
                               style="border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.375rem 0.625rem; font-size: 0.875rem; outline: none; font-family: inherit;"
                               onfocus="this.style.borderColor='#ed2537'"
                               onblur="this.style.borderColor='#d1d5db'" />
                        <button type="submit" style="color: #16a34a; background: none; border: none; cursor: pointer; line-height: 0; padding: 0.25rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <button type="button" wire:click="cancelEditName" style="color: #9ca3af; background: none; border: none; cursor: pointer; line-height: 0; padding: 0.25rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                            </svg>
                        </button>
                    </form>
                    @error('editName')
                        <p style="font-size: 0.75rem; color: #dc2626; margin: 0.25rem 0 0 0;">{{ $message }}</p>
                    @enderror
                @else
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin: 0;">{{ $selectedRole->name }}</h3>
                        <button type="button"
                                wire:click="startEditName"
                                style="color: #9ca3af; background: none; border: none; cursor: pointer; line-height: 0; padding: 0.25rem;"
                                onmouseover="this.style.color='#6b7280'"
                                onmouseout="this.style.color='#9ca3af'"
                                title="Rename role">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem;">
                                <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                            </svg>
                        </button>
                    </div>
                    <button type="button"
                            wire:click="deleteRole"
                            wire:confirm="Are you sure you want to delete this role?"
                            style="color: #9ca3af; background: none; border: none; cursor: pointer; line-height: 0; padding: 0.25rem;"
                            onmouseover="this.style.color='#ef4444'"
                            onmouseout="this.style.color='#9ca3af'"
                            title="Delete role">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 1 .7.798l-.2 4a.75.75 0 0 1-1.496-.076l.2-4a.75.75 0 0 1 .796-.722Zm3.638.798a.75.75 0 0 0-1.496-.076l-.2 4a.75.75 0 0 0 1.496.076l.2-4Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Permissions Checkboxes --}}
            <p style="font-size: 0.875rem; font-weight: 500; color: #374151; margin: 0 0 0.75rem 0;">Resource Access</p>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
                @foreach($resourceOptions as $key => $label)
                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; color: #374151; transition: all 0.15s;"
                           onmouseover="this.style.borderColor='#d1d5db'; this.style.backgroundColor='#f9fafb'"
                           onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='transparent'">
                        <input type="checkbox"
                               wire:model="permissions"
                               value="{{ $key }}"
                               style="accent-color: #ed2537; width: 1rem; height: 1rem; cursor: pointer;" />
                        {{ $label }}
                    </label>
                @endforeach
            </div>

            {{-- Save Button --}}
            <div style="display: flex; justify-content: flex-end; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #f3f4f6;">
                <button type="button"
                        wire:click="savePermissions"
                        style="background-color: #ed2537; color: white; font-weight: 600; font-size: 0.875rem; padding: 0.5rem 1.5rem; border-radius: 0.5rem; border: none; cursor: pointer; font-family: inherit;">
                    Save Permissions
                </button>
            </div>
        </div>
    @else
        <div style="text-align: center; padding: 3rem 0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 3rem; height: 3rem; margin: 0 auto; color: #9ca3af;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
            <h3 style="margin-top: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #111827;">Select a role</h3>
            <p style="margin-top: 0.25rem; font-size: 0.875rem; color: #6b7280;">Choose a role from the dropdown to view and edit its permissions.</p>
        </div>
    @endif

    {{-- Add Role Modal --}}
    @if($showAddModal)
        <div style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center;"
             x-data
             x-on:keydown.escape.window="$wire.set('showAddModal', false)">
            {{-- Backdrop --}}
            <div wire:click="$set('showAddModal', false)"
                 style="position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(2px);"></div>

            {{-- Modal Content --}}
            <div style="position: relative; background: white; border-radius: 0.75rem; padding: 1.5rem; width: 100%; max-width: 32rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); margin: 1rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                    <h2 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0;">Add Role</h2>
                    <button type="button"
                            wire:click="$set('showAddModal', false)"
                            style="color: #9ca3af; background: none; border: none; cursor: pointer; line-height: 0; padding: 0.25rem;"
                            onmouseover="this.style.color='#6b7280'"
                            onmouseout="this.style.color='#9ca3af'">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="addRole">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Role Name</label>
                        <input type="text"
                               wire:model="newRoleName"
                               placeholder="e.g. Manager"
                               style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box; font-family: inherit;"
                               onfocus="this.style.borderColor='#ed2537'; this.style.boxShadow='0 0 0 1px #ed2537'"
                               onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'" />
                        @error('newRoleName')
                            <p style="font-size: 0.75rem; color: #dc2626; margin: 0.25rem 0 0 0;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;">Resource Access</label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;">
                            @foreach($resourceOptions as $key => $label)
                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; color: #374151;"
                                       onmouseover="this.style.borderColor='#d1d5db'; this.style.backgroundColor='#f9fafb'"
                                       onmouseout="this.style.borderColor='#e5e7eb'; this.style.backgroundColor='transparent'">
                                    <input type="checkbox"
                                           wire:model="newPermissions"
                                           value="{{ $key }}"
                                           style="accent-color: #ed2537; width: 1rem; height: 1rem; cursor: pointer;" />
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid #f3f4f6;">
                        <button type="button"
                                wire:click="$set('showAddModal', false)"
                                style="font-size: 0.875rem; font-weight: 500; color: #6b7280; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #d1d5db; background: white; cursor: pointer; font-family: inherit;">
                            Cancel
                        </button>
                        <button type="submit"
                                style="background-color: #ed2537; color: white; font-weight: 600; font-size: 0.875rem; padding: 0.5rem 1.5rem; border-radius: 0.5rem; border: none; cursor: pointer; font-family: inherit;">
                            Create Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
