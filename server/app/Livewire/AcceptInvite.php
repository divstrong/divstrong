<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class AcceptInvite extends Component
{
    public string $token;
    public ?User $user = null;
    public string $password = '';
    public string $password_confirmation = '';
    public bool $invalid = false;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->user = User::where('invite_token', $token)->first();

        if (! $this->user) {
            $this->invalid = true;
        }
    }

    public function accept(): void
    {
        $this->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $this->user->update([
            'password' => Hash::make($this->password),
            'invite_token' => null,
        ]);

        Auth::login($this->user);

        $this->redirect('/admin');
    }

    public function render()
    {
        return view('livewire.accept-invite')
            ->layout('components.layouts.invite');
    }
}
