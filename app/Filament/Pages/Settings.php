<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Setting;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

class Settings extends Page
{

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.settings';

    public string $activeTab = 'general';

    // General tab fields
    public ?string $company_name = '';
    public ?string $email = '';
    public ?string $phone = '';
    public ?string $address1 = '';
    public ?string $address2 = '';
    public ?string $city = '';
    public ?string $state = '';
    public ?string $zip = '';
    public ?string $existing_logo = '';

    // Categories tab
    public array $categories = [];

    public function mount(): void
    {
        $settings = Setting::instance();

        $this->company_name = $settings->company_name ?? '';
        $this->email = $settings->email ?? '';
        $this->phone = $settings->phone ?? '';
        $this->address1 = $settings->address1 ?? '';
        $this->address2 = $settings->address2 ?? '';
        $this->city = $settings->city ?? '';
        $this->state = $settings->state ?? '';
        $this->zip = $settings->zip ?? '';
        $this->existing_logo = $settings->logo ?? '';

        $this->categories = Category::orderBy('sort_order')
            ->get()
            ->map(fn ($cat) => ['name' => $cat->name])
            ->toArray();
    }

    public function saveGeneral(): void
    {
        $settings = Setting::instance();

        $settings->update([
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
        ]);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();
    }

    public function saveCategories(): void
    {
        $names = collect($this->categories)
            ->pluck('name')
            ->filter(fn ($n) => filled($n))
            ->unique()
            ->values();

        // Remove categories no longer in the list
        Category::whereNotIn('name', $names)->delete();

        // Upsert categories with sort order
        foreach ($names as $i => $name) {
            Category::updateOrCreate(
                ['name' => $name],
                ['sort_order' => $i],
            );
        }

        // Refresh the local state
        $this->categories = Category::orderBy('sort_order')
            ->get()
            ->map(fn ($cat) => ['name' => $cat->name])
            ->toArray();

        Notification::make()
            ->success()
            ->title('Categories saved')
            ->send();
    }
}
