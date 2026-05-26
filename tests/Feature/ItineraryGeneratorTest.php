<?php

use App\Livewire\ItineraryGenerator;
use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('it validates children ages with a maximum of 12 years', function () {
    $user = User::factory()->create();
    actingAs($user);

    Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [5, 13]) // 13 is invalid (max is 12)
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '27-10-2026')
        ->call('nextStep')
        ->assertHasErrors(['childrenAges.1']);

    Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [5, 12]) // 12 is valid
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '27-10-2026')
        ->call('nextStep')
        ->assertHasNoErrors();
});

test('it saves the optional arrivingTime field in the database', function () {
    $user = User::factory()->create();
    actingAs($user);

    $destination = Destination::create(['name' => 'إسطنبول']);

    Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [5, 10])
        ->set('destinations', [$destination->id])
        ->set('arrivingDate', '20-10-2026')
        ->set('arrivingTime', '14:30') // Optional time
        ->set('leavingDate', '27-10-2026')
        ->call('nextStep')
        ->set('finalSellingPrice', 1500)
        ->call('saveItinerary')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('itineraries', [
        'customer_name' => 'محمد أحمد',
    ]);

    $itinerary = Itinerary::first();
    expect($itinerary->arriving_date->format('Y-m-d'))->toBe('2026-10-20');
    expect($itinerary->leaving_date->format('Y-m-d'))->toBe('2026-10-27');
    expect($itinerary->data['arrivingTime'])->toBe('14:30');
});

test('it navigates through the 3-step booking details wizard', function () {
    $user = User::factory()->create();
    actingAs($user);

    $destination = Destination::create(['name' => 'إسطنبول']);
    $accommodation = Accommodation::create([
        'name' => 'فندق النخبة',
        'type' => '5 نجوم',
        'default_buying_price' => 100,
    ]);

    Livewire::test(ItineraryGenerator::class)
        ->assertSet('currentStep', 1)
        // Step 1 validation fails if empty
        ->call('nextStep')
        ->assertHasErrors(['customerName'])
        // Fill step 1
        ->set('customerName', 'محمد أحمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [5, 10])
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '27-10-2026')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 2)
        // Step 2 validation fails if accommodations invalid
        ->call('nextStep')
        ->assertHasErrors(['selectedAccommodations.0.accommodation_id'])
        // Fill step 2 details
        ->set('destinations', [$destination->id])
        ->set('selectedAccommodations.0.accommodation_id', $accommodation->id)
        ->set('selectedAccommodations.0.nights', 7)
        ->set('selectedAccommodations.0.buying_price', 100)
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 3)
        // Ensure nextStep does not exceed step 3
        ->call('nextStep')
        ->assertSet('currentStep', 3)
        // Can navigate back
        ->call('previousStep')
        ->assertSet('currentStep', 2)
        ->call('previousStep')
        ->assertSet('currentStep', 1);
});
