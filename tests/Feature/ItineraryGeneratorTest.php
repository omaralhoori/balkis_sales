<?php

use App\Livewire\ItineraryGenerator;
use App\Models\Accommodation;
use App\Models\Car;
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

test('it saves itinerary as draft by default and pins it with deposit when pinned', function () {
    $user = User::factory()->create(['email' => 'employee@example.com']);
    actingAs($user);

    $destination = Destination::create(['name' => 'إسطنبول']);

    // 1. Save Itinerary (draft)
    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [5, 10])
        ->set('destinations', [$destination->id])
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '27-10-2026')
        ->call('nextStep')
        ->set('finalSellingPrice', 1500)
        ->call('saveItinerary')
        ->assertHasNoErrors();

    $itinerary = Itinerary::first();
    expect($itinerary->is_pinned)->toBeFalse();
    expect($itinerary->deposit)->toBeNull();

    // 2. Pin Itinerary with deposit
    $component->set('deposit', 500)
        ->call('pinItinerary')
        ->assertHasNoErrors();

    $itinerary->refresh();
    expect($itinerary->is_pinned)->toBeTrue();
    expect($itinerary->deposit)->toBe(500.0);

    // 3. Regular user editing pinned itinerary should be blocked (403)
    expect($component->instance()->isEditable)->toBeFalse();

    // Attempting to save again as regular user should abort
    $component->call('saveItinerary')
        ->assertStatus(403);

    // 4. Super Admin editing pinned itinerary is allowed
    config(['auth.super_admin_email' => 'admin@example.com']);
    $adminUser = User::factory()->create(['email' => 'admin@example.com']);
    actingAs($adminUser);

    // Reload the component as super admin
    $adminComponent = Livewire::test(ItineraryGenerator::class, ['id' => $itinerary->id]);
    expect($adminComponent->instance()->isEditable)->toBeTrue();

    $adminComponent->set('finalSellingPrice', 1600)
        ->call('saveItinerary')
        ->assertHasNoErrors();

    $itinerary->refresh();
    expect($itinerary->data['finalSellingPrice'])->toEqual(1600);
});

test('it renders video url in voucher if accommodation has one', function () {
    $destination = Destination::create(['name' => 'أنطاليا']);
    $accommodation = Accommodation::create([
        'name' => 'فندق أنطاليا الفاخر',
        'type' => 'فندق',
        'default_buying_price' => 100,
        'default_selling_price' => 150,
        'destination_id' => $destination->id,
        'video_url' => 'https://youtube.com/watch?v=12345',
    ]);

    $view = view('pdf.voucher', [
        'customerName' => 'سليم',
        'adultsCount' => 2,
        'childrenAges' => [],
        'destinations' => [$destination->name],
        'arrivingDate' => '20-10-2026',
        'arrivingTime' => '',
        'leavingDate' => '25-10-2026',
        'totalDays' => 6,
        'totalNights' => 5,
        'selectedAccommodations' => [
            ['accommodation_id' => $accommodation->id, 'nights' => 5, 'buying_price' => 100, 'note' => ''],
        ],
        'includeRentalCar' => false,
        'selectedCarId' => null,
        'carBuyingPrice' => 0,
        'dailyTours' => [],
        'totalBuyingPrice' => 500,
        'finalSellingPrice' => 800,
        'deposit' => null,
        'remaining' => 800,
        'additionalDetails' => '',
        'accommodations' => Accommodation::all(),
        'tours' => Tour::all(),
        'cars' => Car::all(),
    ]);

    $html = $view->render();
    expect($html)->toContain('https://youtube.com/watch?v=12345');
    expect($html)->toContain('رابط الفيديو:');
});

test('it sets default accommodation nights to itinerary total nights and decrements previous when adding new one', function () {
    $destination = Destination::create(['name' => 'إسطنبول']);

    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد احمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [])
        ->set('destinations', [$destination->id])
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '25-10-2026') // 5 nights
        ->call('nextStep'); // Transition to step 2, which calls addAccommodation if empty

    // First accommodation nights should default to total nights (5)
    $accommodations = $component->get('selectedAccommodations');
    expect(count($accommodations))->toBe(1);
    expect($accommodations[0]['nights'])->toBe(5);

    // Adding second accommodation should decrement the first one's nights and set new one's nights to 1
    $component->call('addAccommodation');
    $accommodations = $component->get('selectedAccommodations');
    expect(count($accommodations))->toBe(2);
    expect($accommodations[0]['nights'])->toBe(4);
    expect($accommodations[1]['nights'])->toBe(1);

    // Adding third accommodation should decrement the second one's nights (which is 1, so it cannot be decremented)
    // First should remain 4, second should remain 1, third should default to 1
    $component->call('addAccommodation');
    $accommodations = $component->get('selectedAccommodations');
    expect(count($accommodations))->toBe(3);
    expect($accommodations[0]['nights'])->toBe(4);
    expect($accommodations[1]['nights'])->toBe(1);
    expect($accommodations[2]['nights'])->toBe(1);
});
