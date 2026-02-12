<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'general'"
            wire:click="$set('activeTab', 'general')"
            icon="heroicon-o-building-office"
        >
            General
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'categories'"
            wire:click="$set('activeTab', 'categories')"
            icon="heroicon-o-tag"
        >
            Categories
        </x-filament::tabs.item>
    </x-filament::tabs>

    {{-- General Tab --}}
    <div x-show="$wire.activeTab === 'general'" style="margin-top: 1.5rem;">
        <form wire:submit="saveGeneral">
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin: 0 0 1rem 0;">Company Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Company Name</label>
                        <input type="text" wire:model="company_name" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Email</label>
                        <input type="email" wire:model="email" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Phone</label>
                        <input type="text" wire:model="phone" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box;" />
                    </div>
                </div>
            </div>

            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin: 0 0 1rem 0;">Address</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Address Line 1</label>
                        <input type="text" wire:model="address1" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">Address Line 2</label>
                        <input type="text" wire:model="address2" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">City</label>
                        <input type="text" wire:model="city" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">State</label>
                        <input type="text" wire:model="state" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.25rem;">ZIP Code</label>
                        <input type="text" wire:model="zip" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box;" />
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" style="background-color: #ed2537; color: white; font-weight: 600; font-size: 0.875rem; padding: 0.5rem 1.5rem; border-radius: 0.5rem; border: none; cursor: pointer;">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    {{-- Categories Tab --}}
    <div x-show="$wire.activeTab === 'categories'" style="margin-top: 1.5rem;">
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin: 0;">Service Categories</h3>
                    <p style="font-size: 0.875rem; color: #6b7280; margin: 0.25rem 0 0 0;">Manage categories used in Scope Library and Services.</p>
                </div>
                <button type="button"
                        wire:click="$set('categories', [...$wire.categories, {'name': ''}])"
                        style="display: inline-flex; align-items: center; gap: 0.375rem; background-color: #ed2537; color: white; font-weight: 600; font-size: 0.875rem; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1rem; height: 1rem;">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                    Add Category
                </button>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                @foreach($categories as $index => $category)
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 0.875rem; color: #9ca3af; width: 1.5rem; text-align: right;">{{ $index + 1 }}.</span>
                        <input type="text"
                               wire:model="categories.{{ $index }}.name"
                               placeholder="Category name"
                               style="flex: 1; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; box-sizing: border-box;" />
                        <button type="button"
                                wire:click="$set('categories', {{ json_encode(collect($categories)->forget($index)->values()->toArray()) }})"
                                style="padding: 0.375rem; color: #9ca3af; background: none; border: none; cursor: pointer; line-height: 0;"
                                onmouseover="this.style.color='#ef4444'"
                                onmouseout="this.style.color='#9ca3af'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 1 .7.798l-.2 4a.75.75 0 0 1-1.496-.076l.2-4a.75.75 0 0 1 .796-.722Zm3.638.798a.75.75 0 0 0-1.496-.076l-.2 4a.75.75 0 0 0 1.496.076l.2-4Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>

            @if(empty($categories))
                <div style="text-align: center; padding: 2rem 0; color: #6b7280; font-size: 0.875rem;">
                    No categories yet. Click "Add Category" to get started.
                </div>
            @endif
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="button"
                    wire:click="saveCategories"
                    style="background-color: #ed2537; color: white; font-weight: 600; font-size: 0.875rem; padding: 0.5rem 1.5rem; border-radius: 0.5rem; border: none; cursor: pointer;">
                Save Categories
            </button>
        </div>
    </div>
</x-filament-panels::page>
