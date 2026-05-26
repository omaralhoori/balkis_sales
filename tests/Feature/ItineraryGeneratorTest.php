<?php

use App\Livewire\ItineraryGenerator;
use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\Tour;
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

test('it filters accommodations and tours reactively by selected destinations', function () {
    $user = User::factory()->create();
    actingAs($user);

    $destination1 = Destination::create(['name' => 'إسطنبول']);
    $destination2 = Destination::create(['name' => 'أنطاليا']);

    $accommodation1 = Accommodation::create([
        'name' => 'فندق إسطنبول',
        'type' => '5 نجوم',
        'default_buying_price' => 120,
        'destination_id' => $destination1->id,
    ]);

    $accommodation2 = Accommodation::create([
        'name' => 'فندق أنطاليا',
        'type' => '5 نجوم',
        'default_buying_price' => 100,
        'destination_id' => $destination2->id,
    ]);

    $tour1 = Tour::create([
        'name' => 'رحلة إسطنبول',
        'type' => 'خاص VIP',
        'default_buying_price' => 150,
        'destination_id' => $destination1->id,
    ]);

    $tour2 = Tour::create([
        'name' => 'رحلة أنطاليا',
        'type' => 'خاص VIP',
        'default_buying_price' => 130,
        'destination_id' => $destination2->id,
    ]);

    // 1. Initial state (no destinations selected) -> lists should be empty
    $component = Livewire::test(ItineraryGenerator::class);
    $viewData = $component->instance()->render()->getData();
    expect($viewData['accommodations'])->toBeEmpty();
    expect($viewData['tours'])->toBeEmpty();

    // 2. Select destination1 -> should only show destination1 accommodations and tours
    $component->set('destinations', [$destination1->id]);
    $viewData = $component->instance()->render()->getData();
    expect($viewData['accommodations']->pluck('id')->toArray())->toEqual([$accommodation1->id]);
    expect($viewData['tours']->pluck('id')->toArray())->toEqual([$tour1->id]);

    // 3. Select both destinations -> should show both
    $component->set('destinations', [$destination1->id, $destination2->id]);
    $viewData = $component->instance()->render()->getData();
    expect($viewData['accommodations']->pluck('id')->toArray())->toEqualCanonicalizing([$accommodation1->id, $accommodation2->id]);
    expect($viewData['tours']->pluck('id')->toArray())->toEqualCanonicalizing([$tour1->id, $tour2->id]);

    // 4. Set selections, then change destination -> should auto-clear invalid selections
    $component->set('selectedAccommodations', [
        ['accommodation_id' => $accommodation1->id, 'buying_price' => 120, 'nights' => 3, 'note' => ''],
        ['accommodation_id' => $accommodation2->id, 'buying_price' => 100, 'nights' => 4, 'note' => ''],
    ]);

    $component->set('dailyTours', [
        1 => ['tour_id' => $tour1->id, 'buying_price' => 150, 'date' => '20-10-2026'],
        2 => ['tour_id' => $tour2->id, 'buying_price' => 130, 'date' => '21-10-2026'],
    ]);

    // Restrict destination to destination1 only
    $component->set('destinations', [$destination1->id]);

    // Expect selectedAccommodations to only contain accommodation1
    $selectedAccs = $component->get('selectedAccommodations');
    expect(count($selectedAccs))->toBe(1);
    expect($selectedAccs[0]['accommodation_id'])->toBe($accommodation1->id);

    // Expect dailyTours to have tour2 cleared (empty string)
    $dailyTours = $component->get('dailyTours');
    expect($dailyTours[1]['tour_id'])->toBe($tour1->id);
    expect($dailyTours[2]['tour_id'])->toBe('');
    expect($dailyTours[2]['buying_price'])->toBe(0);
});
