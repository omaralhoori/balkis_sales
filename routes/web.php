<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/login', \App\Livewire\Auth\Login::class)->name('login');
Route::post('/logout', function() { 
    Auth::logout(); 
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login'); 
})->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', \App\Livewire\ItineraryGenerator::class)->name('home');
    Route::get('/itineraries', \App\Livewire\ItineraryList::class)->name('itineraries.index');
});
