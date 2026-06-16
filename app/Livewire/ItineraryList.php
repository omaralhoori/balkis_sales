<?php

namespace App\Livewire;

use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ItineraryList extends Component
{
    public $selectedEmployeeId = '';

    public function isAdminOrSuperAdmin(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->role === 'admin' || $user->email === config('auth.super_admin_email');
    }

    public function deleteItinerary($id)
    {
        if (! $this->isAdminOrSuperAdmin()) {
            abort(403, 'غير مصرح لك بإجراء هذه العملية.');
        }

        $itinerary = Itinerary::findOrFail($id);
        $itinerary->update(['deleted_by' => Auth::id()]);
        $itinerary->delete();

        session()->flash('message', 'تم حذف الطلب بنجاح.');
    }

    public function loadItinerary($id)
    {
        $query = Itinerary::query();
        if (! $this->isAdminOrSuperAdmin()) {
            $query->where('user_id', Auth::id());
        }
        $itinerary = $query->findOrFail($id);

        return redirect()->route('home', ['id' => $itinerary->id]);
    }

    public function render()
    {
        $query = Itinerary::query();
        if (! $this->isAdminOrSuperAdmin()) {
            $query->where('user_id', Auth::id());
        } else {
            if ($this->selectedEmployeeId) {
                $query->where('user_id', $this->selectedEmployeeId);
            }
        }
        $itineraries = $query->orderBy('created_at', 'desc')->with(['user', 'logs.user'])->get();
        $employees = User::orderBy('name')->get();

        return view('livewire.itinerary-list', [
            'itineraries' => $itineraries,
            'employees' => $employees,
        ])->layout('layouts.app');
    }
}
