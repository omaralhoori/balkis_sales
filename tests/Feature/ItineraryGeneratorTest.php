<?php

use App\Livewire\ItineraryGenerator;
use App\Livewire\ItineraryList;
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
    $accommodation = Accommodation::create([
        'name' => 'فندق النخبة',
        'type' => '5 نجوم',
        'default_buying_price' => 100,
        'destination_id' => $destination->id,
    ]);

    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [5, 10])
        ->set('arrivingDate', '20-10-2026')
        ->set('arrivingTime', '14:30')
        ->set('leavingDate', '27-10-2026') // 7 nights
        ->set('leavingTime', '18:00')
        ->call('nextStep');

    for ($i = 0; $i < 7; $i++) {
        $component->set("dailySlots.{$i}.accommodation.destination_id", $destination->id)
            ->set("dailySlots.{$i}.accommodation.accommodation_id", $accommodation->id);
    }

    $component->call('nextStep')
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
        'destination_id' => $destination->id,
    ]);

    $component = Livewire::test(ItineraryGenerator::class)
        ->assertSet('currentStep', 1)
        // Step 1 validation fails if empty
        ->call('nextStep')
        ->assertHasErrors(['customerName'])
        // Fill step 1
        ->set('customerName', 'محمد أحمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [5, 10])
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '27-10-2026') // 7 nights
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 2)
        // Step 2 validation fails if dailySlots accommodations are empty
        ->call('nextStep')
        ->assertHasErrors(['dailySlots.0.accommodation.destination_id', 'dailySlots.0.accommodation.accommodation_id']);

    // Fill step 2 details
    for ($i = 0; $i < 7; $i++) {
        $component->set("dailySlots.{$i}.accommodation.destination_id", $destination->id)
            ->set("dailySlots.{$i}.accommodation.accommodation_id", $accommodation->id)
            ->set("dailySlots.{$i}.accommodation.buying_price", 100);
    }

    $component->call('nextStep')
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

test('it filters accommodations and tours reactively by daily slots destinations', function () {
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
        ->set('leavingDate', '22-10-2026') // 2 nights, 3 days
        ->call('nextStep');

    // Slot 0 (Day 1): Set destination 1, select accommodation 1, select tour 1
    $component->set('dailySlots.0.accommodation.destination_id', $destination1->id)
        ->set('dailySlots.0.accommodation.accommodation_id', $accommodation1->id)
        ->set('dailySlots.0.tour.destination_id', $destination1->id)
        ->set('dailySlots.0.tour.tour_id', $tour1->id);

    $component->instance()->updatedDailySlots($accommodation1->id, 'dailySlots.0.accommodation.accommodation_id');
    $component->instance()->updatedDailySlots($tour1->id, 'dailySlots.0.tour.tour_id');

    // Assert that buying prices are set automatically
    expect($component->get('dailySlots.0.accommodation.buying_price'))->toEqual(120);
    expect($component->get('dailySlots.0.tour.buying_price'))->toEqual(150);

    // If destination changes for Slot 0 to destination 2, it should clear selected accommodation and tour
    $component->set('dailySlots.0.accommodation.destination_id', $destination2->id);
    $component->instance()->updatedDailySlots($destination2->id, 'dailySlots.0.accommodation.destination_id');

    expect($component->get('dailySlots.0.accommodation.accommodation_id'))->toBe('');
    expect($component->get('dailySlots.0.accommodation.buying_price'))->toEqual(0);

    $component->set('dailySlots.0.tour.destination_id', $destination2->id);
    $component->instance()->updatedDailySlots($destination2->id, 'dailySlots.0.tour.destination_id');

    expect($component->get('dailySlots.0.tour.tour_id'))->toBe('');
    expect($component->get('dailySlots.0.tour.buying_price'))->toEqual(0);
});

test('it saves itinerary as draft by default and pins it with deposit when pinned', function () {
    $user = User::factory()->create(['email' => 'employee@example.com']);
    actingAs($user);

    $destination = Destination::create(['name' => 'إسطنبول']);
    $accommodation = Accommodation::create([
        'name' => 'فندق النخبة',
        'type' => '5 نجوم',
        'default_buying_price' => 100,
        'destination_id' => $destination->id,
    ]);

    // 1. Save Itinerary (draft)
    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('adultsCount', 2)
        ->set('childrenAges', [5, 10])
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '27-10-2026') // 7 nights
        ->call('nextStep');

    for ($i = 0; $i < 7; $i++) {
        $component->set("dailySlots.{$i}.accommodation.destination_id", $destination->id)
            ->set("dailySlots.{$i}.accommodation.accommodation_id", $accommodation->id);
    }

    $component->call('nextStep')
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

    $dailySlots = [];
    for ($i = 0; $i < 5; $i++) {
        $dailySlots[] = [
            'date' => '20-10-2026',
            'day_number' => $i + 1,
            'accommodation' => [
                'destination_id' => $destination->id,
                'accommodation_id' => $accommodation->id,
                'buying_price' => 100,
                'note' => '',
            ],
            'tour' => [
                'destination_id' => '',
                'tour_id' => '',
                'buying_price' => 0,
            ],
        ];
    }
    $dailySlots[] = [
        'date' => '25-10-2026',
        'day_number' => 6,
        'accommodation' => [
            'destination_id' => '',
            'accommodation_id' => '',
            'buying_price' => 0,
            'note' => '',
        ],
        'tour' => [
            'destination_id' => '',
            'tour_id' => '',
            'buying_price' => 0,
        ],
    ];

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
        'dailySlots' => $dailySlots,
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

test('it recalculates daily slots when dates change', function () {
    $user = User::factory()->create();
    actingAs($user);

    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد احمد')
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '22-10-2026') // 2 nights, 3 days
        ->call('nextStep');

    $slots = $component->get('dailySlots');
    expect(count($slots))->toBe(3);
    expect($slots[0]['day_number'])->toBe(1);
    expect($slots[0]['date'])->toBe('20-10-2026');
    expect($slots[1]['date'])->toBe('21-10-2026');
    expect($slots[2]['date'])->toBe('22-10-2026');

    // Expand itinerary duration (e.g. 4 nights, 5 days)
    $component->set('leavingDate', '24-10-2026');
    $component->call('calculateDays');

    $slots = $component->get('dailySlots');
    expect(count($slots))->toBe(5);
    expect($slots[4]['day_number'])->toBe(5);
    expect($slots[4]['date'])->toBe('24-10-2026');
});

test('it performs backward compatibility migration on mount', function () {
    $user = User::factory()->create();
    actingAs($user);

    $destination = Destination::create(['name' => 'إسطنبول']);
    $accommodation = Accommodation::create([
        'name' => 'فندق النخبة',
        'type' => '5 نجوم',
        'default_buying_price' => 100,
        'destination_id' => $destination->id,
    ]);

    // Create an itinerary with segments data
    $itinerary = Itinerary::create([
        'user_id' => $user->id,
        'customer_name' => 'زبون قديم',
        'destinations' => [$destination->id],
        'arriving_date' => '2026-10-20',
        'leaving_date' => '2026-10-25',
        'total_days' => 6,
        'total_nights' => 5,
        'data' => [
            'adultsCount' => 2,
            'childrenAges' => [],
            'arrivingTime' => '',
            'leavingTime' => '',
            'includeRentalCar' => false,
            'selectedCarId' => null,
            'carBuyingPrice' => 0,
            'finalSellingPrice' => 1000,
            'segments' => [
                [
                    'destinations' => [$destination->id],
                    'nights' => 5,
                    'accommodations' => [
                        ['accommodation_id' => $accommodation->id, 'nights' => 5, 'buying_price' => 100, 'note' => 'ملاحظة قديمة'],
                    ],
                    'tours' => [
                        ['date' => '20-10-2026', 'tour_id' => '', 'buying_price' => 0],
                    ],
                ],
            ],
        ],
    ]);

    $component = Livewire::test(ItineraryGenerator::class, ['id' => $itinerary->id]);

    // Assert dailySlots property was populated correctly from segments
    $slots = $component->get('dailySlots');
    expect(count($slots))->toBe(6);
    expect($slots[0]['accommodation']['accommodation_id'])->toBe($accommodation->id);
    expect($slots[0]['accommodation']['note'])->toBe('ملاحظة قديمة');
    expect($slots[0]['accommodation']['buying_price'])->toBe(100);
});

test('it downloads voucher PDF with custom footer settings', function () {
    $user = User::factory()->create();
    actingAs($user);

    Setting::updateOrCreate(['key' => 'voucher_footer_bottom'], ['value' => '15']);
    Setting::updateOrCreate(['key' => 'voucher_footer_height'], ['value' => '45']);

    $destination = Destination::create(['name' => 'إسطنبول']);
    $accommodation = Accommodation::create([
        'name' => 'فندق النخبة',
        'type' => '5 نجوم',
        'default_buying_price' => 100,
        'destination_id' => $destination->id,
    ]);

    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '25-10-2026') // 5 nights
        ->call('nextStep');

    for ($i = 0; $i < 5; $i++) {
        $component->set("dailySlots.{$i}.accommodation.destination_id", $destination->id)
            ->set("dailySlots.{$i}.accommodation.accommodation_id", $accommodation->id);
    }

    $component->call('nextStep')
        ->set('finalSellingPrice', 1000)
        ->call('downloadPdf')
        ->assertStatus(200);
});

test('it validates optional whatsapp number and stores it properly', function () {
    $user = User::factory()->create();
    actingAs($user);

    $destination = Destination::create(['name' => 'إسطنبول']);
    $accommodation = Accommodation::create([
        'name' => 'فندق النخبة',
        'type' => '5 نجوم',
        'default_buying_price' => 100,
        'destination_id' => $destination->id,
    ]);

    // 1. Without WhatsApp (nullable)
    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('customerWhatsapp', '')
        ->set('adultsCount', 2)
        ->set('childrenAges', [])
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '22-10-2026')
        ->call('nextStep')
        ->assertHasNoErrors();

    $component->set('dailySlots.0.accommodation.destination_id', $destination->id)
        ->set('dailySlots.0.accommodation.accommodation_id', $accommodation->id)
        ->set('dailySlots.1.accommodation.destination_id', $destination->id)
        ->set('dailySlots.1.accommodation.accommodation_id', $accommodation->id)
        ->call('nextStep')
        ->set('finalSellingPrice', 1500)
        ->call('saveItinerary')
        ->assertHasNoErrors();

    $itinerary = Itinerary::latest('id')->first();
    expect($itinerary->customer_whatsapp)->toBeNull();
    expect($itinerary->data['customerWhatsapp'])->toBe('');

    // 2. With WhatsApp
    $component2 = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('customerWhatsapp', '05391234567')
        ->set('countryCode', '90')
        ->set('adultsCount', 2)
        ->set('childrenAges', [])
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '22-10-2026')
        ->call('nextStep')
        ->assertHasNoErrors();

    $component2->set('dailySlots.0.accommodation.destination_id', $destination->id)
        ->set('dailySlots.0.accommodation.accommodation_id', $accommodation->id)
        ->set('dailySlots.1.accommodation.destination_id', $destination->id)
        ->set('dailySlots.1.accommodation.accommodation_id', $accommodation->id)
        ->call('nextStep')
        ->set('finalSellingPrice', 1500)
        ->call('saveItinerary')
        ->assertHasNoErrors();

    $itinerary2 = Itinerary::latest('id')->first();
    expect($itinerary2->customer_whatsapp)->toBe('905391234567');
});

test('it calculates buying price with car exclusions and includes daily tours', function () {
    $user = User::factory()->create();
    actingAs($user);

    $destination = Destination::create(['name' => 'إسطنبول']);
    $accommodation = Accommodation::create([
        'name' => 'فندق النخبة',
        'type' => '5 نجوم',
        'default_buying_price' => 100,
        'destination_id' => $destination->id,
    ]);
    $tour = Tour::create([
        'name' => 'جولة إسطنبول',
        'type' => 'خاص VIP',
        'default_buying_price' => 80,
        'destination_id' => $destination->id,
    ]);

    $component = Livewire::test(ItineraryGenerator::class)
        ->set('customerName', 'محمد أحمد')
        ->set('arrivingDate', '20-10-2026')
        ->set('leavingDate', '23-10-2026') // 3 nights, 4 days
        ->call('nextStep');

    // Fill accommodations
    for ($i = 0; $i < 3; $i++) {
        $component->set("dailySlots.{$i}.accommodation.destination_id", $destination->id)
            ->set("dailySlots.{$i}.accommodation.accommodation_id", $accommodation->id)
            ->set("dailySlots.{$i}.accommodation.buying_price", 100);
    }

    // Set rental car
    $component->set('includeRentalCar', true)
        ->set('carBuyingPrice', 50); // total 4 days * 50 = 200

    // Without exclusions: 3 nights of acc (300) + 4 days of car (200) = 500
    expect($component->get('totalBuyingPrice'))->toEqual(500);

    // Exclude first day: 3 nights of acc (300) + 3 days of car (150) = 450
    $component->set('excludeCarFirstDay', true);
    expect($component->get('totalBuyingPrice'))->toEqual(450);

    // Select tour for first day: tour price (80) + 3 nights of acc (300) + 3 days of car (150) = 530
    $component->set('dailySlots.0.tour.destination_id', $destination->id)
        ->set('dailySlots.0.tour.tour_id', $tour->id)
        ->set('dailySlots.0.tour.buying_price', 80);
    expect($component->get('totalBuyingPrice'))->toEqual(530);

    // Exclude last day as well: tour price (80) + 3 nights of acc (300) + 2 days of car (100) = 480
    $component->set('excludeCarLastDay', true);
    expect($component->get('totalBuyingPrice'))->toEqual(480);
});

test('it handles previous orders filters and deletion for admin and super admin', function () {
    $superAdmin = User::factory()->create(['email' => 'superadmin@example.com']);
    config(['auth.super_admin_email' => 'superadmin@example.com']);

    $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.com']);
    $employee1 = User::factory()->create(['role' => 'employee']);
    $employee2 = User::factory()->create(['role' => 'employee']);

    $itinerary1 = Itinerary::create([
        'user_id' => $employee1->id,
        'customer_name' => 'زبون موظف 1',
        'arriving_date' => '2026-10-20',
        'leaving_date' => '2026-10-25',
        'total_days' => 6,
        'total_nights' => 5,
        'data' => [],
    ]);

    $itinerary2 = Itinerary::create([
        'user_id' => $employee2->id,
        'customer_name' => 'زبون موظف 2',
        'arriving_date' => '2026-10-20',
        'leaving_date' => '2026-10-25',
        'total_days' => 6,
        'total_nights' => 5,
        'data' => [],
    ]);

    // 1. Employee 1 only sees their own itinerary
    actingAs($employee1);
    Livewire::test(ItineraryList::class)
        ->assertViewHas('itineraries', function ($its) use ($itinerary1) {
            return $its->count() === 1 && $its->first()->id === $itinerary1->id;
        });

    // 2. Admin sees all itineraries and can filter
    actingAs($admin);
    Livewire::test(ItineraryList::class)
        ->assertViewHas('itineraries', function ($its) {
            return $its->count() === 2;
        })
        ->set('selectedEmployeeId', $employee1->id)
        ->assertViewHas('itineraries', function ($its) use ($itinerary1) {
            return $its->count() === 1 && $its->first()->id === $itinerary1->id;
        });

    // 3. Admin can delete itineraries
    Livewire::test(ItineraryList::class)
        ->call('deleteItinerary', $itinerary2->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('itineraries', ['id' => $itinerary2->id]);
    $this->assertDatabaseHas('itineraries', ['id' => $itinerary1->id]);
});
