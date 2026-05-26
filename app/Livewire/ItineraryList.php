<?php

namespace App\Livewire;

use App\Models\Itinerary;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ItineraryList extends Component
{
    public function loadItinerary($id)
    {
        $query = Itinerary::query();
        if (Auth::user()?->email !== config('auth.super_admin_email')) {
            $query->where('user_id', Auth::id());
        }
        $itinerary = $query->findOrFail($id);

        return redirect()->route('home', ['id' => $itinerary->id]);
    }

    public function render()
    {
        $query = Itinerary::query();
        if (Auth::user()?->email !== config('auth.super_admin_email')) {
            $query->where('user_id', Auth::id());
        }
        $itineraries = $query->orderBy('created_at', 'desc')->get();

        return view('livewire.itinerary-list', [
            'itineraries' => $itineraries,
        ])->layout('layouts.app');
    }
}
