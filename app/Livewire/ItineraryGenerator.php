<?php

namespace App\Livewire;

use App\Models\Accommodation;
use App\Models\Car;
use App\Models\Destination;
use App\Models\Itinerary;
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

    public ?int $selectedCarId = null;

    public float $carBuyingPrice = 0;

    public float $finalSellingPrice = 0;

    public bool $isPinned = false;

    public ?float $deposit = null;

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
                $this->selectedCarId = $data['selectedCarId'] ?? null;
                $this->carBuyingPrice = $data['carBuyingPrice'] ?? 0;
                $this->finalSellingPrice = $data['finalSellingPrice'] ?? 0;

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
            'customerWhatsapp' => 'required',
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
            if (! $this->includeRentalCar && ! empty($slot['tour']['tour_id'])) {
                $total += (float) ($slot['tour']['buying_price'] ?? 0);
            }
        }
        if ($this->includeRentalCar) {
            $total += ((float) $this->carBuyingPrice * $this->totalDays);
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

        $dataToSave = [
            'adultsCount' => $this->adultsCount,
            'childrenAges' => $this->childrenAges,
            'arrivingTime' => $this->arrivingTime,
            'leavingTime' => $this->leavingTime,
            'includeRentalCar' => $this->includeRentalCar,
            'selectedCarId' => $this->selectedCarId,
            'carBuyingPrice' => $this->carBuyingPrice,
            'dailySlots' => $this->dailySlots,
            'voucherNotes' => $this->voucherNotes,
            'totalBuyingPrice' => $this->totalBuyingPrice,
            'finalSellingPrice' => $this->finalSellingPrice,
            'customer_whatsapp' => $this->countryCode . preg_replace('/^0+/', '', $this->customerWhatsapp),
            
        ];

        $arrDate = Carbon::createFromFormat('d-m-Y', $this->arrivingDate)->format('Y-m-d');
        $levDate = Carbon::createFromFormat('d-m-Y', $this->leavingDate)->format('Y-m-d');

        if ($this->itineraryId) {
            $query = Itinerary::query();
            if (Auth::user()?->email !== config('auth.super_admin_email')) {
                $query->where('user_id', Auth::id());
            }
            $it = $query->findOrFail($this->itineraryId);
            $it->update([
                'customer_name' => $this->customerName,
                'destinations' => $this->destinations,
                'arriving_date' => $arrDate,
                'leaving_date' => $levDate,
                'total_days' => $this->totalDays,
                'total_nights' => $this->totalNights,
                'data' => $dataToSave,
                'is_pinned' => $this->isPinned,
                'deposit' => $this->deposit,
            ]);
            session()->flash('message', 'تم تحديث البرنامج السياحي بنجاح!');
        } else {
            $it = Itinerary::create([
                'user_id' => Auth::id(),
                'customer_name' => $this->customerName,
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

        $dataToSave = [
            'adultsCount' => $this->adultsCount,
            'childrenAges' => $this->childrenAges,
            'arrivingTime' => $this->arrivingTime,
            'leavingTime' => $this->leavingTime,
            'includeRentalCar' => $this->includeRentalCar,
            'selectedCarId' => $this->selectedCarId,
            'carBuyingPrice' => $this->carBuyingPrice,
            'dailySlots' => $this->dailySlots,
            'voucherNotes' => $this->voucherNotes,
            'totalBuyingPrice' => $this->totalBuyingPrice,
            'finalSellingPrice' => $this->finalSellingPrice,
        ];

        $arrDate = Carbon::createFromFormat('d-m-Y', $this->arrivingDate)->format('Y-m-d');
        $levDate = Carbon::createFromFormat('d-m-Y', $this->leavingDate)->format('Y-m-d');

        if ($this->itineraryId) {
            $query = Itinerary::query();
            if (Auth::user()?->email !== config('auth.super_admin_email')) {
                $query->where('user_id', Auth::id());
            }
            $it = $query->findOrFail($this->itineraryId);
            $it->update([
                'customer_name' => $this->customerName,
                'destinations' => $this->destinations,
                'arriving_date' => $arrDate,
                'leaving_date' => $levDate,
                'total_days' => $this->totalDays,
                'total_nights' => $this->totalNights,
                'data' => $dataToSave,
                'is_pinned' => true,
                'deposit' => $this->deposit,
            ]);
        } else {
            $it = Itinerary::create([
                'user_id' => Auth::id(),
                'customer_name' => $this->customerName,
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
        ];

        $html = view('pdf.voucher', $data)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 25,
            'margin_bottom' => $footerHeight,
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
        $cleanPhone = preg_replace('/^0+/', '', $this->customerWhatsapp);
$url = 'https://wa.me/' . $this->countryCode . $cleanPhone . '?text=' . urlencode($text);
        $this->dispatch('open-url', url: $url);
    }

    public function render()
    {
        return view('livewire.itinerary-generator', [
            'cars' => Car::all(),
            'dbDestinations' => Destination::all(),
        ])->layout('layouts.app');
    }
    public function updatedCustomerWhatsapp($value)
    {
        $this->customerWhatsapp = preg_replace('/^0+/', '', $value);
    }
}
