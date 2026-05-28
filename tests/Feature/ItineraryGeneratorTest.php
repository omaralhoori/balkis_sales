<?php

use App\Livewire\ItineraryGenerator;
use App\Models\Accommodation;
use App\Models\Car;
use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\Setting;
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

test('it saves the optional arrivingTime and leavingTime fields in the database', function () {
    $user = User::factory()->create();
    actingAs($user);

    $destination = Destination::create(['name' => 'إسطنبول']);

    Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [5, 10])
        ->set('arrivingDate', '20-10-2026')
        ->set('arrivingTime', '14:30')
        ->set('leavingDate', '27-10-2026')
        ->set('leavingTime', '18:00')
        ->call('nextStep')
        ->set('segments.0.destinations', [$destination->id])
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
    expect($itinerary->data['leavingTime'])->toBe('18:00');
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
        // Step 2 validation fails if segments incomplete
        ->call('nextStep')
        ->assertHasErrors(['segments.0.destinations'])
        // Fill step 2 details
        ->set('segments.0.destinations', [$destination->id])
        ->set('segments.0.accommodations.0.accommodation_id', $accommodation->id)
        ->set('segments.0.accommodations.0.nights', 7)
        ->set('segments.0.accommodations.0.buying_price', 100)
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

test('it filters accommodations and tours reactively by segment destinations', function () {
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

    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '27-10-2026')
        ->call('nextStep')
        ->set('segments.0.destinations', [$destination1->id, $destination2->id]);

    $component->set('segments.0.accommodations', [
        ['accommodation_id' => $accommodation1->id, 'buying_price' => 120, 'nights' => 3, 'note' => ''],
        ['accommodation_id' => $accommodation2->id, 'buying_price' => 100, 'nights' => 4, 'note' => ''],
    ]);

    $component->set('segments.0.tours', [
        0 => ['tour_id' => $tour1->id, 'buying_price' => 150, 'date' => '20-10-2026'],
        1 => ['tour_id' => $tour2->id, 'buying_price' => 130, 'date' => '21-10-2026'],
    ]);

    // Restrict segment destinations to destination1 only
    $component->set('segments.0.destinations', [$destination1->id]);
    $component->instance()->updatedSegments([$destination1->id], 'segments.0.destinations');

    // Expect selected accommodations to only contain accommodation1
    $accommodations = $component->get('segments.0.accommodations');
    expect(count($accommodations))->toBe(1);
    expect($accommodations[0]['accommodation_id'])->toBe($accommodation1->id);

    // Expect tours to clear tour2 (not in destination1)
    $tours = $component->get('segments.0.tours');
    expect($tours[0]['tour_id'])->toBe($tour1->id);
    expect($tours[1]['tour_id'])->toBe('');
    expect($tours[1]['buying_price'])->toBe(0);
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
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '27-10-2026')
        ->call('nextStep')
        ->set('segments.0.destinations', [$destination->id])
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

    $component->call('saveItinerary')
        ->assertStatus(403);

    // 4. Super Admin editing pinned itinerary is allowed
    config(['auth.super_admin_email' => 'admin@example.com']);
    $adminUser = User::factory()->create(['email' => 'admin@example.com']);
    actingAs($adminUser);

    $adminComponent = Livewire::test(ItineraryGenerator::class, ['id' => $itinerary->id]);
    expect($adminComponent->instance()->isEditable)->toBeTrue();

    $adminComponent->set('finalSellingPrice', 1600)
        ->call('saveItinerary')
        ->assertHasNoErrors();

    $itinerary->refresh();
    expect($itinerary->data['finalSellingPrice'])->toEqual(1600);
});

test('it renders video url and voucher notes in voucher PDF', function () {
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
        'leavingTime' => '',
        'totalDays' => 6,
        'totalNights' => 5,
        'segments' => [
            [
                'destinations' => [$destination->id],
                'nights' => 5,
                'accommodations' => [
                    ['accommodation_id' => $accommodation->id, 'nights' => 5, 'buying_price' => 100, 'note' => ''],
                ],
                'tours' => [],
            ],
        ],
        'voucherNotes' => 'تنبيه: يرجى إحضار جوازات السفر الأصلية.',
        'includeRentalCar' => false,
        'selectedCarId' => null,
        'carBuyingPrice' => 0,
        'totalBuyingPrice' => 500,
        'finalSellingPrice' => 800,
        'deposit' => null,
        'remaining' => 800,
        'additionalDetails' => 'تواصلوا معنا عبر بلقيس',
        'accommodations' => Accommodation::all(),
        'tours' => Tour::all(),
        'cars' => Car::all(),
    ]);

    $html = $view->render();
    expect($html)->toContain('https://youtube.com/watch?v=12345');
    expect($html)->toContain('رابط الفيديو:');
    expect($html)->toContain('تنبيه: يرجى إحضار جوازات السفر الأصلية.');
    expect($html)->toContain('تواصلوا معنا عبر بلقيس');
});

test('it sets default accommodation nights to itinerary total nights and decrements previous when adding new one inside segment', function () {
    $destination = Destination::create(['name' => 'إسطنبول']);

    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد احمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [])
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '25-10-2026') // 5 nights
        ->call('nextStep'); // Transition to step 2, calls addSegment

    // First accommodation nights should default to total segment nights (5)
    $segments = $component->get('segments');
    expect(count($segments))->toBe(1);
    expect($segments[0]['nights'])->toBe(5);
    expect(count($segments[0]['accommodations']))->toBe(1);
    expect($segments[0]['accommodations'][0]['nights'])->toBe(5);

    // Adding second accommodation in segment 0 should decrement the first one's nights and set new one's nights to 1
    $component->call('addAccommodationToSegment', 0);
    $segments = $component->get('segments');
    expect(count($segments[0]['accommodations']))->toBe(2);
    expect($segments[0]['accommodations'][0]['nights'])->toBe(4);
    expect($segments[0]['accommodations'][1]['nights'])->toBe(1);
});

test('it manages segments and validates segment total nights', function () {
    $destination = Destination::create(['name' => 'إسطنبول']);

    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد احمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [])
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '25-10-2026') // 5 nights
        ->call('nextStep'); // 1 segment of 5 nights created

    // Add another segment
    // Total nights = 5. Smart segment addition decrements 1 night from first segment (5 -> 4) and sets new segment to 1 night.
    $component->call('addSegment');
    $segments = $component->get('segments');
    expect(count($segments))->toBe(2);
    expect($segments[0]['nights'])->toBe(4);
    expect($segments[1]['nights'])->toBe(1);

    // Exceed validation: set segment 1 nights to 2 (total 4+2=6 nights, exceeds 5 nights)
    $component->set('segments.1.nights', 2);
    $component->set('segments.0.destinations', [$destination->id]);
    $component->set('segments.1.destinations', [$destination->id]);
    $component->set('segments.0.accommodations.0.accommodation_id', 1); // mock id
    $component->set('segments.1.accommodations.0.accommodation_id', 2); // mock id
    $component->call('nextStep')
        ->assertHasErrors(['segment_nights_total']);

    // Fix validation: set segment 1 nights back to 1, validation passes
    $component->set('segments.1.nights', 1);
    $component->call('nextStep')
        ->assertHasNoErrors();
});

test('it downloads voucher PDF with custom footer settings', function () {
    $user = User::factory()->create();
    actingAs($user);

    Setting::updateOrCreate(['key' => 'voucher_footer_bottom'], ['value' => '15']);
    Setting::updateOrCreate(['key' => 'voucher_footer_height'], ['value' => '45']);

    $destination = Destination::create(['name' => 'إسطنبول']);

    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '25-10-2026')
        ->call('nextStep')
        ->set('segments.0.destinations', [$destination->id])
        ->call('nextStep')
        ->set('finalSellingPrice', 1000)
        ->call('downloadPdf')
        ->assertStatus(200);
});
