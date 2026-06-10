<?php

namespace App\Livewire;

use App\Models\Accommodation;
use App\Models\Car;
use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\ItineraryLog;
use App\Models\Setting;
use App\Models\Tour;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Mpdf\Mpdf;

class ItineraryGenerator extends Component
{
    public ?int $itineraryId = null;

    public int $currentStep = 1;

    // Step 1: Info & Dates
    public string $customerName = '';

    public string $customerWhatsapp = '';

    public $countryCode = '90';

    public int $adultsCount = 1;

    public array $childrenAges = [];

    public array $destinations = [];

    public string $arrivingDate = '';

    public string $arrivingTime = '';

    public string $leavingDate = '';

    public string $leavingTime = '';

    public int $totalDays = 0;

    public int $totalNights = 0;

    // Step 2: Booking Details (Daily Slots)
    public array $dailySlots = [];

    public string $voucherNotes = '';

    // Step 3: Cars
    public bool $includeRentalCar = false;

    public bool $excludeCarFirstDay = false;

    public bool $excludeCarLastDay = false;

    public ?int $selectedCarId = null;

    public float $carBuyingPrice = 0;

    public float $finalSellingPrice = 0;

    public bool $isPinned = false;

    public ?float $deposit = null;

    public string $accSortBy = 'name';

    public string $accSortOrder = 'asc';

    public string $accStarsFilter = 'all';

    public string $accTypeFilter = 'all';

    public string $carSortBy = 'name';

    public string $carSortOrder = 'asc';

    public function mount(?int $id = null)
    {
        $id = $id ?? request()->query('id');
        if ($id) {
            $query = Itinerary::query();
            if (Auth::user()?->email !== config('auth.super_admin_email')) {
                $query->where('user_id', Auth::id());
            }
            $it = $query->find($id);
            if ($it) {
                $this->itineraryId = $it->id;
                $this->customerName = $it->customer_name;
                $this->destinations = $it->destinations ?? [];
                $this->arrivingDate = $it->arriving_date->format('d-m-Y');
                $this->leavingDate = $it->leaving_date->format('d-m-Y');
                $this->isPinned = $it->is_pinned;
                $this->deposit = $it->deposit;

                $data = $it->data;
                $this->adultsCount = $data['adultsCount'] ?? 1;
                $this->childrenAges = $data['childrenAges'] ?? [];
                $this->arrivingTime = $data['arrivingTime'] ?? '';
                $this->leavingTime = $data['leavingTime'] ?? '';
                $this->voucherNotes = $data['voucherNotes'] ?? '';
                $this->includeRentalCar = $data['includeRentalCar'] ?? false;
                $this->excludeCarFirstDay = $data['excludeCarFirstDay'] ?? false;
                $this->excludeCarLastDay = $data['excludeCarLastDay'] ?? false;
                $this->selectedCarId = $data['selectedCarId'] ?? null;
                $this->carBuyingPrice = $data['carBuyingPrice'] ?? 0;
                $this->finalSellingPrice = $data['finalSellingPrice'] ?? 0;
                $this->customerWhatsapp = $data['customerWhatsapp'] ?? '';
                $this->countryCode = $data['countryCode'] ?? '90';
                if (empty($this->customerWhatsapp) && ! empty($it->customer_whatsapp)) {
                    $knownCodes = ['966', '971', '965', '974', '973', '968', '20', '970', '90'];
                    $foundCode = false;
                    foreach ($knownCodes as $code) {
                        if (str_starts_with($it->customer_whatsapp, $code)) {
                            $this->countryCode = $code;
                            $this->customerWhatsapp = substr($it->customer_whatsapp, strlen($code));
                            $foundCode = true;
                            break;
                        }
                    }
                    if (! $foundCode) {
                        $this->customerWhatsapp = $it->customer_whatsapp;
                    }
                }

                // Load daily slots with backward compatibility fallbacks
                if (isset($data['dailySlots'])) {
                    $this->dailySlots = $data['dailySlots'];
                } elseif (isset($data['segments'])) {
                    $this->migrateSegmentsToDailySlots($data['segments']);
                } else {
                    $oldAccs = $data['selectedAccommodations'] ?? [];
                    $oldTours = isset($data['dailyTours']) ? array_values($data['dailyTours']) : [];
                    $this->migrateOldFormatToDailySlots($oldAccs, $oldTours);
                }
            }
        }

        if (! $this->arrivingDate) {
            $this->arrivingDate = Carbon::now()->format('d-m-Y');
            $this->leavingDate = Carbon::now()->addDays(3)->format('d-m-Y');
        }
        $this->calculateDays();
    }

    protected function migrateSegmentsToDailySlots(array $segments)
    {
        try {
            $startDate = Carbon::createFromFormat('d-m-Y', $this->arrivingDate)->startOfDay();
        } catch (\Exception $e) {
            $startDate = Carbon::now()->startOfDay();
        }

        $dailySlots = [];
        $accommodationsList = [];
        $toursList = [];

        foreach ($segments as $seg) {
            $destId = $seg['destinations'][0] ?? '';
            foreach ($seg['accommodations'] ?? [] as $acc) {
                $accNights = (int) ($acc['nights'] ?? 1);
                for ($n = 0; $n < $accNights; $n++) {
                    $accModel = ! empty($acc['accommodation_id']) ? Accommodation::find($acc['accommodation_id']) : null;
                    $accommodationsList[] = [
                        'destination_id' => $accModel ? $accModel->destination_id : $destId,
                        'accommodation_id' => $acc['accommodation_id'] ?? '',
                        'buying_price' => $acc['buying_price'] ?? 0,
                        'note' => $acc['note'] ?? '',
                        'room_type' => $acc['room_type'] ?? '',
                        'custom_room_type' => $acc['custom_room_type'] ?? '',
                    ];
                }
            }
            foreach ($seg['tours'] ?? [] as $tour) {
                $tourModel = ! empty($tour['tour_id']) ? Tour::find($tour['tour_id']) : null;
                $toursList[$tour['date']] = [
                    'destination_id' => $tourModel ? $tourModel->destination_id : $destId,
                    'tour_id' => $tour['tour_id'] ?? '',
                    'buying_price' => $tour['buying_price'] ?? 0,
                ];
            }
        }

        $totalDays = $this->totalDays > 0 ? $this->totalDays : 1;
        for ($d = 0; $d < $totalDays; $d++) {
            $dateStr = $startDate->copy()->addDays($d)->format('d-m-Y');
            $dailySlots[] = [
                'date' => $dateStr,
                'day_number' => $d + 1,
                'accommodation' => $accommodationsList[$d] ?? [
                    'destination_id' => '',
                    'accommodation_id' => '',
                    'buying_price' => 0,
                    'note' => '',
                    'room_type' => '',
                    'custom_room_type' => '',
                ],
                'tour' => $toursList[$dateStr] ?? [
                    'destination_id' => '',
                    'tour_id' => '',
                    'buying_price' => 0,
                ],
            ];
        }

        $this->dailySlots = $dailySlots;
    }

    protected function migrateOldFormatToDailySlots(array $oldAccs, array $oldTours)
    {
        try {
            $startDate = Carbon::createFromFormat('d-m-Y', $this->arrivingDate)->startOfDay();
        } catch (\Exception $e) {
            $startDate = Carbon::now()->startOfDay();
        }

        $dailySlots = [];
        $accommodationsList = [];
        foreach ($oldAccs as $acc) {
            $accNights = (int) ($acc['nights'] ?? 1);
            for ($n = 0; $n < $accNights; $n++) {
                $accModel = ! empty($acc['accommodation_id']) ? Accommodation::find($acc['accommodation_id']) : null;
                $accommodationsList[] = [
                    'destination_id' => $accModel ? $accModel->destination_id : '',
                    'accommodation_id' => $acc['accommodation_id'] ?? '',
                    'buying_price' => $acc['buying_price'] ?? 0,
                    'note' => $acc['note'] ?? '',
                    'room_type' => $acc['room_type'] ?? '',
                    'custom_room_type' => $acc['custom_room_type'] ?? '',
                ];
            }
        }

        $toursList = [];
        foreach ($oldTours as $tour) {
            $tourModel = ! empty($tour['tour_id']) ? Tour::find($tour['tour_id']) : null;
            $toursList[$tour['date']] = [
                'destination_id' => $tourModel ? $tourModel->destination_id : '',
                'tour_id' => $tour['tour_id'] ?? '',
                'buying_price' => $tour['buying_price'] ?? 0,
            ];
        }

        $totalDays = $this->totalDays > 0 ? $this->totalDays : 1;
        for ($d = 0; $d < $totalDays; $d++) {
            $dateStr = $startDate->copy()->addDays($d)->format('d-m-Y');
            $dailySlots[] = [
                'date' => $dateStr,
                'day_number' => $d + 1,
                'accommodation' => $accommodationsList[$d] ?? [
                    'destination_id' => '',
                    'accommodation_id' => '',
                    'buying_price' => 0,
                    'note' => '',
                    'room_type' => '',
                    'custom_room_type' => '',
                ],
                'tour' => $toursList[$dateStr] ?? [
                    'destination_id' => '',
                    'tour_id' => '',
                    'buying_price' => 0,
                ],
            ];
        }

        $this->dailySlots = $dailySlots;
    }

    public function updatedArrivingDate()
    {
        $this->calculateDays();
    }

    public function updatedLeavingDate()
    {
        $this->calculateDays();
    }

    public function calculateDays()
    {
        if ($this->arrivingDate && $this->leavingDate) {
            try {
                $start = Carbon::createFromFormat('d-m-Y', $this->arrivingDate)->startOfDay();
                $end = Carbon::createFromFormat('d-m-Y', $this->leavingDate)->startOfDay();
                if ($end->greaterThan($start)) {
                    $this->totalNights = $start->diffInDays($end);
                    $this->totalDays = $this->totalNights + 1;
                } else {
                    $this->totalNights = 0;
                    $this->totalDays = 1;
                }

                $this->recalculateDailySlots();
            } catch (\Exception $e) {
                // Ignore parsing errors temporarily if user is typing
            }
        }
    }

    public function recalculateDailySlots()
    {
        if (! $this->arrivingDate) {
            return;
        }
        try {
            $startDate = Carbon::createFromFormat('d-m-Y', $this->arrivingDate)->startOfDay();
            $newSlots = [];

            for ($d = 0; $d < $this->totalDays; $d++) {
                $dateStr = $startDate->copy()->addDays($d)->format('d-m-Y');
                $existingSlot = $this->dailySlots[$d] ?? null;

                $newSlots[] = [
                    'date' => $dateStr,
                    'day_number' => $d + 1,
                    'accommodation' => [
                        'destination_id' => $existingSlot['accommodation']['destination_id'] ?? '',
                        'accommodation_id' => $existingSlot['accommodation']['accommodation_id'] ?? '',
                        'buying_price' => $existingSlot['accommodation']['buying_price'] ?? 0,
                        'note' => $existingSlot['accommodation']['note'] ?? '',
                        'room_type' => $existingSlot['accommodation']['room_type'] ?? '',
                        'custom_room_type' => $existingSlot['accommodation']['custom_room_type'] ?? '',
                    ],
                    'tour' => [
                        'destination_id' => $existingSlot['tour']['destination_id'] ?? '',
                        'tour_id' => $existingSlot['tour']['tour_id'] ?? '',
                        'buying_price' => $existingSlot['tour']['buying_price'] ?? 0,
                    ],
                ];
            }

            $this->dailySlots = $newSlots;
        } catch (\Exception $e) {
            // Ignore date parsing issues
        }
    }

    public function addChild()
    {
        $this->childrenAges[] = '';
    }

    public function removeChild($index)
    {
        unset($this->childrenAges[$index]);
        $this->childrenAges = array_values($this->childrenAges);
    }

    public function updatedDailySlots($value, $key)
    {
        if (str_contains($key, '.accommodation.destination_id')) {
            $parts = explode('.', $key);
            $dayIndex = (int) $parts[0];
            $destId = $value;
            $accId = $this->dailySlots[$dayIndex]['accommodation']['accommodation_id'] ?? '';
            if ($accId) {
                $acc = Accommodation::find($accId);
                if (! $acc || $acc->destination_id != $destId) {
                    $this->dailySlots[$dayIndex]['accommodation']['accommodation_id'] = '';
                    $this->dailySlots[$dayIndex]['accommodation']['buying_price'] = 0;
                }
            }
        }

        if (str_contains($key, '.tour.destination_id')) {
            $parts = explode('.', $key);
            $dayIndex = (int) $parts[0];
            $destId = $value;
            $tourId = $this->dailySlots[$dayIndex]['tour']['tour_id'] ?? '';
            if ($tourId) {
                $tour = Tour::find($tourId);
                if (! $tour || $tour->destination_id != $destId) {
                    $this->dailySlots[$dayIndex]['tour']['tour_id'] = '';
                    $this->dailySlots[$dayIndex]['tour']['buying_price'] = 0;
                }
            }
        }

        if (str_contains($key, '.accommodation.accommodation_id')) {
            $parts = explode('.', $key);
            if (count($parts) === 3) {
                $dayIndex = (int) $parts[0];
                $this->dailySlots[$dayIndex]['accommodation']['room_type'] = '';
                $this->dailySlots[$dayIndex]['accommodation']['custom_room_type'] = '';
                if ($value) {
                    $acc = Accommodation::find($value);
                    if ($acc) {
                        $this->dailySlots[$dayIndex]['accommodation']['buying_price'] = $acc->default_buying_price;
                    }
                }
            }
        }

        if (str_contains($key, '.tour.tour_id')) {
            $parts = explode('.', $key);
            if (count($parts) === 3) {
                $dayIndex = (int) $parts[0];
                if ($value) {
                    $tour = Tour::find($value);
                    if ($tour) {
                        $this->dailySlots[$dayIndex]['tour']['buying_price'] = $tour->default_buying_price;
                    }
                }
            }
        }
    }

    public function updatedSelectedCarId($value)
    {
        if ($value) {
            $car = Car::find($value);
            if ($car) {
                $this->carBuyingPrice = $car->default_buying_price;
            }
        }
    }

    public function nextStep()
    {
        if ($this->currentStep == 1) {
            $this->validate([
                'customerName' => 'required',
                'customerWhatsapp' => 'nullable',
                'adultsCount' => 'required|numeric|min:1',
                'childrenAges.*' => 'required|numeric|min:0|max:12',
                'arrivingDate' => 'required',
                'arrivingTime' => 'nullable|string',
                'leavingDate' => 'required',
                'leavingTime' => 'nullable|string',
            ]);
            if (empty($this->dailySlots)) {
                $this->recalculateDailySlots();
            }
        } elseif ($this->currentStep == 2) {
            $rules = [
                'dailySlots.*.accommodation.destination_id' => 'nullable',
                'dailySlots.*.accommodation.accommodation_id' => 'nullable',
                'dailySlots.*.accommodation.buying_price' => 'required|numeric|min:0',
                'dailySlots.*.accommodation.note' => 'nullable|string',
                'dailySlots.*.tour.destination_id' => 'nullable',
                'dailySlots.*.tour.tour_id' => 'nullable',
                'dailySlots.*.tour.buying_price' => 'required|numeric|min:0',
            ];

            // Make accommodation required for all nights (the first $totalNights slots)
            for ($i = 0; $i < $this->totalNights; $i++) {
                $rules["dailySlots.{$i}.accommodation.destination_id"] = 'required';
                $rules["dailySlots.{$i}.accommodation.accommodation_id"] = 'required';
            }

            $this->validate($rules, [
                'dailySlots.*.accommodation.destination_id.required' => 'حقل الوجهة مطلوب.',
                'dailySlots.*.accommodation.accommodation_id.required' => 'يرجى اختيار السكن.',
                'dailySlots.*.accommodation.buying_price.required' => 'سعر الشراء مطلوب.',
                'dailySlots.*.tour.buying_price.required' => 'سعر الشراء مطلوب.',
            ]);
        }

        if ($this->currentStep < 3) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function getTotalBuyingPriceProperty()
    {
        $total = 0;
        foreach ($this->dailySlots as $index => $slot) {
            if ($index < $this->totalNights && ! empty($slot['accommodation']['accommodation_id'])) {
                $total += (float) ($slot['accommodation']['buying_price'] ?? 0);
            }
            $hasNoCarOnThisDay = ! $this->includeRentalCar || ($index == 0 && $this->excludeCarFirstDay) || ($index == $this->totalDays - 1 && $this->excludeCarLastDay);
            if ($hasNoCarOnThisDay && ! empty($slot['tour']['tour_id'])) {
                $total += (float) ($slot['tour']['buying_price'] ?? 0);
            }
        }
        if ($this->includeRentalCar) {
            $carDays = $this->totalDays - ($this->excludeCarFirstDay ? 1 : 0) - ($this->excludeCarLastDay ? 1 : 0);
            $total += ((float) $this->carBuyingPrice * max(0, $carDays));
        }

        return $total;
    }

    public function getIsEditableProperty(): bool
    {
        if (! $this->isPinned) {
            return true;
        }

        return Auth::user()?->email === config('auth.super_admin_email');
    }

    public function saveItinerary()
    {
        if (! $this->isEditable) {
            abort(403, 'غير مصرح لك بتعديل هذا البرنامج السياحي لأنه مثبت.');
        }

        $this->validate([
            'finalSellingPrice' => 'required|numeric|min:0',
        ]);

        $allDestIds = [];
        foreach ($this->dailySlots as $slot) {
            if (! empty($slot['accommodation']['destination_id'])) {
                $allDestIds[] = $slot['accommodation']['destination_id'];
            }
            if (! $this->includeRentalCar && ! empty($slot['tour']['destination_id'])) {
                $allDestIds[] = $slot['tour']['destination_id'];
            }
        }
        $this->destinations = array_values(array_unique(array_filter($allDestIds)));

        $whatsappConsolidated = empty($this->customerWhatsapp) ? null : ($this->countryCode.preg_replace('/^0+/', '', $this->customerWhatsapp));

        $dataToSave = [
            'adultsCount' => $this->adultsCount,
            'childrenAges' => $this->childrenAges,
            'arrivingTime' => $this->arrivingTime,
            'leavingTime' => $this->leavingTime,
            'includeRentalCar' => $this->includeRentalCar,
            'excludeCarFirstDay' => $this->excludeCarFirstDay,
            'excludeCarLastDay' => $this->excludeCarLastDay,
            'selectedCarId' => $this->selectedCarId,
            'carBuyingPrice' => $this->carBuyingPrice,
            'dailySlots' => $this->dailySlots,
            'voucherNotes' => $this->voucherNotes,
            'totalBuyingPrice' => $this->totalBuyingPrice,
            'finalSellingPrice' => $this->finalSellingPrice,
            'customerWhatsapp' => $this->customerWhatsapp,
            'countryCode' => $this->countryCode,
        ];

        $arrDate = Carbon::createFromFormat('d-m-Y', $this->arrivingDate)->format('Y-m-d');
        $levDate = Carbon::createFromFormat('d-m-Y', $this->leavingDate)->format('Y-m-d');

        if ($this->itineraryId) {
            $query = Itinerary::query();
            if (Auth::user()?->email !== config('auth.super_admin_email')) {
                $query->where('user_id', Auth::id());
            }
            $it = $query->findOrFail($this->itineraryId);
            $oldModel = $it->toArray();
            $it->update([
                'customer_name' => $this->customerName,
                'customer_whatsapp' => $whatsappConsolidated,
                'destinations' => $this->destinations,
                'arriving_date' => $arrDate,
                'leaving_date' => $levDate,
                'total_days' => $this->totalDays,
                'total_nights' => $this->totalNights,
                'data' => $dataToSave,
                'is_pinned' => $this->isPinned,
                'deposit' => $this->deposit,
            ]);
            $this->trackAndSaveLogs($it, $oldModel, $it->fresh()->toArray());
            session()->flash('message', 'تم تحديث البرنامج السياحي بنجاح!');
        } else {
            $it = Itinerary::create([
                'user_id' => Auth::id(),
                'customer_name' => $this->customerName,
                'customer_whatsapp' => $whatsappConsolidated,
                'destinations' => $this->destinations,
                'arriving_date' => $arrDate,
                'leaving_date' => $levDate,
                'total_days' => $this->totalDays,
                'total_nights' => $this->totalNights,
                'data' => $dataToSave,
                'is_pinned' => false,
                'deposit' => $this->deposit,
            ]);
            $this->itineraryId = $it->id;
            session()->flash('message', 'تم حفظ مسودة البرنامج بنجاح!');
        }
    }

    public function pinItinerary()
    {
        if (! $this->isEditable) {
            abort(403, 'غير مصرح لك بتعديل هذا البرنامج السياحي لأنه مثبت.');
        }

        $this->validate([
            'finalSellingPrice' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0|max:'.$this->finalSellingPrice,
        ]);

        $this->isPinned = true;
        unset($this->isEditable);

        $allDestIds = [];
        foreach ($this->dailySlots as $slot) {
            if (! empty($slot['accommodation']['destination_id'])) {
                $allDestIds[] = $slot['accommodation']['destination_id'];
            }
            if (! $this->includeRentalCar && ! empty($slot['tour']['destination_id'])) {
                $allDestIds[] = $slot['tour']['destination_id'];
            }
        }
        $this->destinations = array_values(array_unique(array_filter($allDestIds)));

        $whatsappConsolidated = empty($this->customerWhatsapp) ? null : ($this->countryCode.preg_replace('/^0+/', '', $this->customerWhatsapp));

        $dataToSave = [
            'adultsCount' => $this->adultsCount,
            'childrenAges' => $this->childrenAges,
            'arrivingTime' => $this->arrivingTime,
            'leavingTime' => $this->leavingTime,
            'includeRentalCar' => $this->includeRentalCar,
            'excludeCarFirstDay' => $this->excludeCarFirstDay,
            'excludeCarLastDay' => $this->excludeCarLastDay,
            'selectedCarId' => $this->selectedCarId,
            'carBuyingPrice' => $this->carBuyingPrice,
            'dailySlots' => $this->dailySlots,
            'voucherNotes' => $this->voucherNotes,
            'totalBuyingPrice' => $this->totalBuyingPrice,
            'finalSellingPrice' => $this->finalSellingPrice,
            'customerWhatsapp' => $this->customerWhatsapp,
            'countryCode' => $this->countryCode,
        ];

        $arrDate = Carbon::createFromFormat('d-m-Y', $this->arrivingDate)->format('Y-m-d');
        $levDate = Carbon::createFromFormat('d-m-Y', $this->leavingDate)->format('Y-m-d');

        if ($this->itineraryId) {
            $query = Itinerary::query();
            if (Auth::user()?->email !== config('auth.super_admin_email')) {
                $query->where('user_id', Auth::id());
            }
            $it = $query->findOrFail($this->itineraryId);
            $oldModel = $it->toArray();
            $it->update([
                'customer_name' => $this->customerName,
                'customer_whatsapp' => $whatsappConsolidated,
                'destinations' => $this->destinations,
                'arriving_date' => $arrDate,
                'leaving_date' => $levDate,
                'total_days' => $this->totalDays,
                'total_nights' => $this->totalNights,
                'data' => $dataToSave,
                'is_pinned' => true,
                'deposit' => $this->deposit,
            ]);
            $this->trackAndSaveLogs($it, $oldModel, $it->fresh()->toArray());
        } else {
            $it = Itinerary::create([
                'user_id' => Auth::id(),
                'customer_name' => $this->customerName,
                'customer_whatsapp' => $whatsappConsolidated,
                'destinations' => $this->destinations,
                'arriving_date' => $arrDate,
                'leaving_date' => $levDate,
                'total_days' => $this->totalDays,
                'total_nights' => $this->totalNights,
                'data' => $dataToSave,
                'is_pinned' => true,
                'deposit' => $this->deposit,
            ]);
            $this->itineraryId = $it->id;
        }

        session()->flash('message', 'تم تثبيت البرنامج بنجاح ولا يمكن التعديل عليه الآن!');
    }

    public function downloadPdf()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        $additionalDetails = $settings['voucher_additional_details'] ?? '';
        $footerBottom = isset($settings['voucher_footer_bottom']) ? (float) $settings['voucher_footer_bottom'] : 10;
        $footerHeight = isset($settings['voucher_footer_height']) ? (float) $settings['voucher_footer_height'] : 35;

        $data = [
            'customerName' => $this->customerName,
            'customerWhatsapp' => empty($this->customerWhatsapp) ? null : ($this->countryCode.preg_replace('/^0+/', '', $this->customerWhatsapp)),
            'adultsCount' => $this->adultsCount,
            'childrenAges' => $this->childrenAges,
            'destinations' => Destination::whereIn('id', $this->destinations)->pluck('name')->toArray(),
            'arrivingDate' => $this->arrivingDate,
            'arrivingTime' => $this->arrivingTime,
            'leavingDate' => $this->leavingDate,
            'leavingTime' => $this->leavingTime,
            'totalDays' => $this->totalDays,
            'totalNights' => $this->totalNights,
            'dailySlots' => $this->dailySlots,
            'voucherNotes' => $this->voucherNotes,
            'includeRentalCar' => $this->includeRentalCar,
            'excludeCarFirstDay' => $this->excludeCarFirstDay,
            'excludeCarLastDay' => $this->excludeCarLastDay,
            'selectedCarId' => $this->selectedCarId,
            'carBuyingPrice' => $this->carBuyingPrice,
            'totalBuyingPrice' => $this->totalBuyingPrice,
            'finalSellingPrice' => $this->finalSellingPrice,
            'deposit' => $this->deposit,
            'remaining' => $this->finalSellingPrice - ($this->deposit ?? 0),
            'additionalDetails' => $additionalDetails,
            'accommodations' => Accommodation::all(),
            'tours' => Tour::all(),
            'cars' => Car::all(),
            'footerHeight' => $footerHeight,
        ];

        $html = view('pdf.voucher', $data)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 25,
            'margin_bottom' => 15,
            'margin_header' => 10,
            'margin_footer' => $footerBottom,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->SetDirectionality('rtl');

        $letterheadPath = public_path('assets/Letterhead.pdf');
        if (file_exists($letterheadPath)) {
            $mpdf->setSourceFile($letterheadPath);
            $tplId = $mpdf->importPage(1);
            $mpdf->SetPageTemplate($tplId);
        }

        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output('', 'S');

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, 'voucher-'.$this->customerName.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function sendWhatsApp()
    {
        $dests = Destination::whereIn('id', $this->destinations)->pluck('name')->implode('، ');

        $text = "*مرحباً {$this->customerName}*\n";
        $text .= "إليك تفاصيل رحلتك السياحية الممتعة:\n\n";
        $text .= "*تاريخ الوصول:* {$this->arrivingDate}".($this->arrivingTime ? " (الوقت: {$this->arrivingTime})" : '')."\n";
        $text .= "*تاريخ المغادرة:* {$this->leavingDate}".($this->leavingTime ? " (الوقت: {$this->leavingTime})" : '')."\n";
        $text .= "*المدة:* {$this->totalDays} أيام / {$this->totalNights} ليالي\n";
        $text .= "*الوجهات:* {$dests}\n";

        $childrenText = count($this->childrenAges) > 0 ? ' و '.count($this->childrenAges).' أطفال (أعمارهم: '.implode('، ', $this->childrenAges).")\n" : "\n";
        $text .= "*عدد الأفراد:* {$this->adultsCount} بالغين".$childrenText;

        if ($this->deposit > 0) {
            $remaining = $this->finalSellingPrice - $this->deposit;
            $text .= "*سعر المبيع الإجمالي:* {$this->finalSellingPrice} $\n";
            $text .= "*العربون المدفوع:* {$this->deposit} $\n";
            $text .= "*المبلغ المتبقي:* {$remaining} $\n\n";
        } else {
            $text .= "*سعر المبيع الإجمالي:* {$this->finalSellingPrice} $\n\n";
        }

        $text .= "يرجى مراجعة ملف الـ PDF المرفق لمشاهدة الجدول التفصيلي للرحلة خطوة بخطوة.\nنتمنى لكم رحلة سعيدة!";
        if (empty($this->customerWhatsapp)) {
            $url = 'https://api.whatsapp.com/send?text='.urlencode($text);
        } else {
            $cleanPhone = preg_replace('/^0+/', '', $this->customerWhatsapp);
            $url = 'https://wa.me/'.$this->countryCode.$cleanPhone.'?text='.urlencode($text);
        }
        $this->dispatch('open-url', url: $url);
    }

    public function render()
    {
        $carsQuery = Car::query();
        if ($this->carSortBy === 'price') {
            $carsQuery->orderBy('default_buying_price', $this->carSortOrder);
        } else {
            $carsQuery->orderBy('car_type', $this->carSortOrder);
        }

        return view('livewire.itinerary-generator', [
            'cars' => $carsQuery->get(),
            'dbDestinations' => Destination::all(),
        ])->layout('layouts.app');
    }

    public function updatedCustomerWhatsapp($value)
    {
        $this->customerWhatsapp = preg_replace('/^0+/', '', $value);
    }

    protected function trackAndSaveLogs(Itinerary $it, array $oldModel, array $newModel): void
    {
        $changes = [];

        // 1. Compare standard fields
        if (($oldModel['customer_name'] ?? '') !== ($newModel['customer_name'] ?? '')) {
            $changes[] = "اسم العميل: من '".($oldModel['customer_name'] ?? 'بلا')."' إلى '".($newModel['customer_name'] ?? 'بلا')."'";
        }

        if (($oldModel['customer_whatsapp'] ?? '') !== ($newModel['customer_whatsapp'] ?? '')) {
            $changes[] = "رقم الواتساب: من '".($oldModel['customer_whatsapp'] ?? 'بلا')."' إلى '".($newModel['customer_whatsapp'] ?? 'بلا')."'";
        }

        $oldArr = isset($oldModel['arriving_date']) ? Carbon::parse($oldModel['arriving_date'])->format('d-m-Y') : '';
        $newArr = isset($newModel['arriving_date']) ? Carbon::parse($newModel['arriving_date'])->format('d-m-Y') : '';
        if ($oldArr !== $newArr) {
            $changes[] = "تاريخ الوصول: من '".($oldArr ?: 'بلا')."' إلى '".($newArr ?: 'بلا')."'";
        }

        $oldLev = isset($oldModel['leaving_date']) ? Carbon::parse($oldModel['leaving_date'])->format('d-m-Y') : '';
        $newLev = isset($newModel['leaving_date']) ? Carbon::parse($newModel['leaving_date'])->format('d-m-Y') : '';
        if ($oldLev !== $newLev) {
            $changes[] = "تاريخ المغادرة: من '".($oldLev ?: 'بلا')."' إلى '".($newLev ?: 'بلا')."'";
        }

        if (($oldModel['total_days'] ?? 0) != ($newModel['total_days'] ?? 0)) {
            $changes[] = "عدد الأيام: من '".($oldModel['total_days'] ?? 0)."' إلى '".($newModel['total_days'] ?? 0)."'";
        }

        if (($oldModel['total_nights'] ?? 0) != ($newModel['total_nights'] ?? 0)) {
            $changes[] = "عدد الليالي: من '".($oldModel['total_nights'] ?? 0)."' إلى '".($newModel['total_nights'] ?? 0)."'";
        }

        $oldDep = $oldModel['deposit'] ?? null;
        $newDep = $newModel['deposit'] ?? null;
        if ($oldDep != $newDep) {
            $changes[] = "العربون: من '".($oldDep ?? 0)."$' إلى '".($newDep ?? 0)."$'";
        }

        if (($oldModel['is_pinned'] ?? false) != ($newModel['is_pinned'] ?? false)) {
            $statusOld = ($oldModel['is_pinned'] ?? false) ? 'مثبت' : 'مسودة';
            $statusNew = ($newModel['is_pinned'] ?? false) ? 'مثبت' : 'مسودة';
            $changes[] = "حالة تثبيت البرنامج: من '{$statusOld}' إلى '{$statusNew}'";
        }

        // 2. Compare data fields
        $oldData = $oldModel['data'] ?? [];
        if (is_string($oldData)) {
            $oldData = json_decode($oldData, true) ?? [];
        }
        $newData = $newModel['data'] ?? [];
        if (is_string($newData)) {
            $newData = json_decode($newData, true) ?? [];
        }

        if (($oldData['adultsCount'] ?? 1) != ($newData['adultsCount'] ?? 1)) {
            $changes[] = "عدد البالغين: من '".($oldData['adultsCount'] ?? 1)."' إلى '".($newData['adultsCount'] ?? 1)."'";
        }

        $oldKids = $oldData['childrenAges'] ?? [];
        $newKids = $newData['childrenAges'] ?? [];
        if ($oldKids !== $newKids) {
            $changes[] = "أعمار الأطفال: من '".implode(', ', $oldKids)."' إلى '".implode(', ', $newKids)."'";
        }

        if (($oldData['arrivingTime'] ?? '') !== ($newData['arrivingTime'] ?? '')) {
            $changes[] = "وقت الوصول: من '".($oldData['arrivingTime'] ?? 'بلا')."' إلى '".($newData['arrivingTime'] ?? 'بلا')."'";
        }

        if (($oldData['leavingTime'] ?? '') !== ($newData['leavingTime'] ?? '')) {
            $changes[] = "وقت المغادرة: من '".($oldData['leavingTime'] ?? 'بلا')."' إلى '".($newData['leavingTime'] ?? 'بلا')."'";
        }

        if (($oldData['voucherNotes'] ?? '') !== ($newData['voucherNotes'] ?? '')) {
            $changes[] = 'ملاحظات الفاوچر تم تعديلها';
        }

        if (($oldData['finalSellingPrice'] ?? 0) != ($newData['finalSellingPrice'] ?? 0)) {
            $changes[] = "السعر الإجمالي (المبيع): من '".($oldData['finalSellingPrice'] ?? 0)."$' إلى '".($newData['finalSellingPrice'] ?? 0)."$'";
        }

        if (($oldData['includeRentalCar'] ?? false) != ($newData['includeRentalCar'] ?? false)) {
            $changes[] = "تضمين سيارة خاصة: من '".(($oldData['includeRentalCar'] ?? false) ? 'نعم' : 'لا')."' إلى '".(($newData['includeRentalCar'] ?? false) ? 'نعم' : 'لا')."'";
        }

        if (($oldData['excludeCarFirstDay'] ?? false) != ($newData['excludeCarFirstDay'] ?? false)) {
            $changes[] = "استثناء السيارة في اليوم الأول: من '".(($oldData['excludeCarFirstDay'] ?? false) ? 'نعم' : 'لا')."' إلى '".(($newData['excludeCarFirstDay'] ?? false) ? 'نعم' : 'لا')."'";
        }

        if (($oldData['excludeCarLastDay'] ?? false) != ($newData['excludeCarLastDay'] ?? false)) {
            $changes[] = "استثناء السيارة في اليوم الأخير: من '".(($oldData['excludeCarLastDay'] ?? false) ? 'نعم' : 'لا')."' إلى '".(($newData['excludeCarLastDay'] ?? false) ? 'نعم' : 'لا')."'";
        }

        if (($oldData['selectedCarId'] ?? null) != ($newData['selectedCarId'] ?? null)) {
            $oldCarName = ($oldData['selectedCarId'] ?? null) ? (Car::find($oldData['selectedCarId'])?->car_type ?? 'غير معروف') : 'لا يوجد';
            $newCarName = ($newData['selectedCarId'] ?? null) ? (Car::find($newData['selectedCarId'])?->car_type ?? 'غير معروف') : 'لا يوجد';
            $changes[] = "نوع السيارة: من '{$oldCarName}' إلى '{$newCarName}'";
        }

        if (($oldData['carBuyingPrice'] ?? 0) != ($newData['carBuyingPrice'] ?? 0)) {
            $changes[] = "سعر شراء السيارة: من '".($oldData['carBuyingPrice'] ?? 0)."$' إلى '".($newData['carBuyingPrice'] ?? 0)."$'";
        }

        // 3. Compare daily slots
        $oldSlots = $oldData['dailySlots'] ?? [];
        $newSlots = $newData['dailySlots'] ?? [];
        $maxSlots = max(count($oldSlots), count($newSlots));

        for ($i = 0; $i < $maxSlots; $i++) {
            $dayNum = $i + 1;
            $oldSlot = $oldSlots[$i] ?? null;
            $newSlot = $newSlots[$i] ?? null;

            if ($oldSlot === null && $newSlot !== null) {
                $changes[] = "تم إضافة اليوم {$dayNum} بتاريخ {$newSlot['date']}";
                if (! empty($newSlot['accommodation']['accommodation_id'])) {
                    $accName = Accommodation::find($newSlot['accommodation']['accommodation_id'])?->name ?? 'غير معروف';
                    $changes[] = "اليوم {$dayNum}: إقامة مضافة: '{$accName}' بسعر '{$newSlot['accommodation']['buying_price']}$'";
                }
                if (! empty($newSlot['tour']['tour_id'])) {
                    $tourName = Tour::find($newSlot['tour']['tour_id'])?->name ?? 'غير معروف';
                    $changes[] = "اليوم {$dayNum}: رحلة مضافة: '{$tourName}' بسعر '{$newSlot['tour']['buying_price']}$'";
                }
            } elseif ($oldSlot !== null && $newSlot === null) {
                $changes[] = "تم حذف اليوم {$dayNum} بتاريخ {$oldSlot['date']}";
            } else {
                // Accommodation Destination
                $oldAccDest = $oldSlot['accommodation']['destination_id'] ?? '';
                $newAccDest = $newSlot['accommodation']['destination_id'] ?? '';
                if ($oldAccDest != $newAccDest) {
                    $oldDestName = Destination::find($oldAccDest)?->name ?? 'بلا وجهة';
                    $newDestName = Destination::find($newAccDest)?->name ?? 'بلا وجهة';
                    $changes[] = "اليوم {$dayNum}: وجهة السكن: من '{$oldDestName}' إلى '{$newDestName}'";
                }

                // Accommodation Name
                $oldAccId = $oldSlot['accommodation']['accommodation_id'] ?? '';
                $newAccId = $newSlot['accommodation']['accommodation_id'] ?? '';
                if ($oldAccId != $newAccId) {
                    $oldAccName = Accommodation::find($oldAccId)?->name ?? 'لا يوجد سكن';
                    $newAccName = Accommodation::find($newAccId)?->name ?? 'لا يوجد سكن';
                    $changes[] = "اليوم {$dayNum}: السكن: من '{$oldAccName}' إلى '{$newAccName}'";
                }

                // Accommodation Buying Price
                $oldAccPrice = $oldSlot['accommodation']['buying_price'] ?? 0;
                $newAccPrice = $newSlot['accommodation']['buying_price'] ?? 0;
                if ($oldAccPrice != $newAccPrice) {
                    $changes[] = "اليوم {$dayNum}: سعر شراء السكن: من '{$oldAccPrice}$' إلى '{$newAccPrice}$'";
                }

                // Accommodation Room Type
                $oldRoomType = $oldSlot['accommodation']['room_type'] ?? '';
                $newRoomType = $newSlot['accommodation']['room_type'] ?? '';
                $oldCustomRoomType = $oldSlot['accommodation']['custom_room_type'] ?? '';
                $newCustomRoomType = $newSlot['accommodation']['custom_room_type'] ?? '';
                $oldRoomText = ($oldRoomType === 'أخرى' || $oldRoomType === 'عدد الأشخاص "يكتب يدويا"') ? ($oldCustomRoomType ?: $oldRoomType) : $oldRoomType;
                $newRoomText = ($newRoomType === 'أخرى' || $newRoomType === 'عدد الأشخاص "يكتب يدويا"') ? ($newCustomRoomType ?: $newRoomType) : $newRoomType;
                if ($oldRoomText !== $newRoomText) {
                    $changes[] = "اليوم {$dayNum}: خيار/نوع الغرفة: من '".($oldRoomText ?: 'بلا')."' إلى '".($newRoomText ?: 'بلا')."'";
                }

                // Accommodation Note
                $oldAccNote = $oldSlot['accommodation']['note'] ?? '';
                $newAccNote = $newSlot['accommodation']['note'] ?? '';
                if ($oldAccNote !== $newAccNote) {
                    $changes[] = "اليوم {$dayNum}: ملاحظة السكن تم تعديلها";
                }

                // Tour Destination
                $oldTourDest = $oldSlot['tour']['destination_id'] ?? '';
                $newTourDest = $newSlot['tour']['destination_id'] ?? '';
                if ($oldTourDest != $newTourDest) {
                    $oldDestName = Destination::find($oldTourDest)?->name ?? 'بلا وجهة';
                    $newDestName = Destination::find($newTourDest)?->name ?? 'بلا وجهة';
                    $changes[] = "اليوم {$dayNum}: وجهة الرحلة: من '{$oldDestName}' إلى '{$newDestName}'";
                }

                // Tour Name
                $oldTourId = $oldSlot['tour']['tour_id'] ?? '';
                $newTourId = $newSlot['tour']['tour_id'] ?? '';
                if ($oldTourId != $newTourId) {
                    $oldTourName = Tour::find($oldTourId)?->name ?? 'سيارة بدون سائق / لا يوجد رحلة';
                    $newTourName = Tour::find($newTourId)?->name ?? 'سيارة بدون سائق / لا يوجد رحلة';
                    $changes[] = "اليوم {$dayNum}: الرحلة: من '{$oldTourName}' إلى '{$newTourName}'";
                }

                // Tour Buying Price
                $oldTourPrice = $oldSlot['tour']['buying_price'] ?? 0;
                $newTourPrice = $newSlot['tour']['buying_price'] ?? 0;
                if ($oldTourPrice != $newTourPrice) {
                    $changes[] = "اليوم {$dayNum}: سعر شراء الرحلة: من '{$oldTourPrice}$' إلى '{$newTourPrice}$'";
                }
            }
        }

        // If there are changes, save them
        if (! empty($changes)) {
            ItineraryLog::create([
                'itinerary_id' => $it->id,
                'user_id' => Auth::id(),
                'changes' => $changes,
            ]);
        }
    }
}
