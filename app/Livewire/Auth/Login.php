<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public $email = '';

    public $password = '';

    public $remember = true;

    public function login()
    {
        $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $this->remember)) {
            session()->regenerate();

            return redirect()->intended('/');
        }

        $this->addError('email', 'البريد الإلكتروني أو كلمة المرور غير صحيحة.');
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.app');
    }
}
