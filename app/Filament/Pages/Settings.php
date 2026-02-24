<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use UnitEnum;

class Settings extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('settings') ?? false;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::instance();

        $this->form->fill([
            'company_name' => $settings->company_name ?? '',
            'email' => $settings->email ?? '',
            'phone' => $settings->phone ?? '',
            'address1' => $settings->address1 ?? '',
            'address2' => $settings->address2 ?? '',
            'city' => $settings->city ?? '',
            'state' => $settings->state ?? '',
            'zip' => $settings->zip ?? '',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('General')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Company Information')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('company_name')
                                            ->label('Company Name')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(255),
                                    ]),
                                Section::make('Address')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('address1')
                                            ->label('Address Line 1')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('address2')
                                            ->label('Address Line 2')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('city')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('state')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('zip')
                                            ->label('ZIP Code')
                                            ->maxLength(255),
                                    ]),
                                Actions::make([
                                    Action::make('saveGeneral')
                                        ->label('Save Settings')
                                        ->action(fn () => $this->saveGeneral()),
                                ])->alignment(Alignment::End),
                            ]),

                        Tab::make('Roles')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Manage Roles')
                                    ->description('Define roles and control which resources each role can access in the sidebar.')
                                    ->schema([
                                        Livewire::make(\App\Livewire\RoleManager::class),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function saveGeneral(): void
    {
        $data = $this->data;
        $settings = Setting::instance();

        $settings->update([
            'company_name' => $data['company_name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address1' => $data['address1'] ?? '',
            'address2' => $data['address2'] ?? '',
            'city' => $data['city'] ?? '',
            'state' => $data['state'] ?? '',
            'zip' => $data['zip'] ?? '',
        ]);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();
    }
}
