<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Itinerary;
use Illuminate\Support\Facades\Auth;

class ItineraryList extends Component
{
    public function loadItinerary($id)
    {
        $itinerary = Itinerary::where('user_id', Auth::id())->findOrFail($id);
        return redirect()->route('home', ['id' => $itinerary->id]);
    }

    public function render()
    {
        $itineraries = Itinerary::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.itinerary-list', [
            'itineraries' => $itineraries
        ])->layout('layouts.app');
    }
}
