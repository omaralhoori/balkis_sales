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

    public int $adultsCount = 1;

    public array $childrenAges = [];

    public array $destinations = [];

    public string $arrivingDate = '';

    public string $arrivingTime = '';

    public string $leavingDate = '';

    public string $leavingTime = '';

    public int $totalDays = 0;

    public int $totalNights = 0;

    // Step 2: Booking Details (Segments)
    public array $segments = [];

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
            $itinerary = $query->find($id);
            if ($itinerary) {
                $this->itineraryId = $itinerary->id;
                $this->customerName = $itinerary->customer_name;
                $this->destinations = $itinerary->destinations ?? [];
                $this->arrivingDate = $itinerary->arriving_date->format('d-m-Y');
                $this->leavingDate = $itinerary->leaving_date->format('d-m-Y');
                $this->isPinned = $itinerary->is_pinned;
                $this->deposit = $itinerary->deposit;

                $data = $itinerary->data;
                $this->adultsCount = $data['adultsCount'] ?? 1;
                $this->childrenAges = $data['childrenAges'] ?? [];
                $this->arrivingTime = $data['arrivingTime'] ?? '';
                $this->leavingTime = $data['leavingTime'] ?? '';
                $this->voucherNotes = $data['voucherNotes'] ?? '';
                $this->includeRentalCar = $data['includeRentalCar'] ?? false;
                $this->selectedCarId = $data['selectedCarId'] ?? null;
                $this->carBuyingPrice = $data['carBuyingPrice'] ?? 0;
                $this->finalSellingPrice = $data['finalSellingPrice'] ?? 0;

                // Load segments with backward compatibility fallback
                if (isset($data['segments'])) {
                    $this->segments = $data['segments'];
                } else {
                    $oldAccs = $data['selectedAccommodations'] ?? [];
                    $oldTours = [];
                    if (isset($data['dailyTours'])) {
                        $oldTours = array_values($data['dailyTours']);
                    }
                    $this->segments = [
                        [
                            'destinations' => $this->destinations,
                            'nights' => $this->totalNights,
                            'accommodations' => $oldAccs,
                            'tours' => $oldTours,
                        ],
                    ];
                }
            }
        }

        if (! $this->arrivingDate) {
            $this->arrivingDate = Carbon::now()->format('d-m-Y');
            $this->leavingDate = Carbon::now()->addDays(3)->format('d-m-Y');
        }
        $this->calculateDays();
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

                // Adjust segment nights to fit the new total nights
                if (empty($this->segments)) {
                    $this->segments = [
                        [
                            'destinations' => $this->destinations,
                            'nights' => $this->totalNights,
                            'accommodations' => [
                                ['accommodation_id' => '', 'buying_price' => 0, 'nights' => $this->totalNights, 'note' => ''],
                            ],
                            'tours' => [],
                        ],
                    ];
                } else {
                    $totalAllocated = collect($this->segments)->sum('nights');
                    if ($totalAllocated !== $this->totalNights) {
                        $diff = $this->totalNights - $totalAllocated;
                        $lastIdx = count($this->segments) - 1;
                        $this->segments[$lastIdx]['nights'] = max(1, $this->segments[$lastIdx]['nights'] + $diff);

                        // Force shrink segments from right to left if exceeding
                        $totalAllocated = collect($this->segments)->sum('nights');
                        if ($totalAllocated > $this->totalNights) {
                            for ($i = count($this->segments) - 1; $i >= 0; $i--) {
                                $currentAllocated = collect($this->segments)->sum('nights');
                                if ($currentAllocated <= $this->totalNights) {
                                    break;
                                }
                                $exceeding = $currentAllocated - $this->totalNights;
                                $canReduce = $this->segments[$i]['nights'] - 1;
                                $reduceBy = min($exceeding, $canReduce);
                                $this->segments[$i]['nights'] -= $reduceBy;
                            }
                        }
                    }
                }

                // Keep single accommodations nights in sync with segment nights
                foreach ($this->segments as $index => $seg) {
                    if (count($this->segments[$index]['accommodations'] ?? []) === 1) {
                        $this->segments[$index]['accommodations'][0]['nights'] = (int) $this->segments[$index]['nights'];
                    }
                }

                $this->recalculateSegmentDates();
            } catch (\Exception $e) {
                // Ignore parsing errors temporarily if user is typing
            }
        }
    }

    public function recalculateSegmentDates()
    {
        if (! $this->arrivingDate) {
            return;
        }
        try {
            $currentDate = Carbon::createFromFormat('d-m-Y', $this->arrivingDate)->startOfDay();
            $totalSegments = count($this->segments);

            for ($i = 0; $i < $totalSegments; $i++) {
                $segNights = (int) ($this->segments[$i]['nights'] ?? 0);
                $daysCount = $segNights;
                if ($i === $totalSegments - 1) {
                    $daysCount = $segNights + 1;
                }

                $newTours = [];
                for ($d = 0; $d < $daysCount; $d++) {
                    $dateStr = $currentDate->copy()->addDays($d)->format('d-m-Y');
                    $existingTour = $this->segments[$i]['tours'][$d] ?? null;
                    $newTours[] = [
                        'tour_id' => $existingTour['tour_id'] ?? '',
                        'buying_price' => $existingTour['buying_price'] ?? 0,
                        'date' => $dateStr,
                    ];
                }

                $this->segments[$i]['tours'] = $newTours;
                $currentDate->addDays($segNights);
            }
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

    public function addSegment()
    {
        $totalAllocatedNights = collect($this->segments)->sum('nights');
        $remainingNights = max(0, $this->totalNights - $totalAllocatedNights);

        $defaultNights = 1;
        if ($remainingNights > 0) {
            $defaultNights = $remainingNights;
        } else {
            $lastIndex = count($this->segments) - 1;
            if ($lastIndex >= 0 && $this->segments[$lastIndex]['nights'] > 1) {
                $this->segments[$lastIndex]['nights']--;
                $defaultNights = 1;
            }
        }

        $this->segments[] = [
            'destinations' => [],
            'nights' => $defaultNights,
            'accommodations' => [
                ['accommodation_id' => '', 'buying_price' => 0, 'nights' => $defaultNights, 'note' => ''],
            ],
            'tours' => [],
        ];

        $this->recalculateSegmentDates();
    }

    public function removeSegment($index)
    {
        if (count($this->segments) > 1) {
            $nightsToReturn = $this->segments[$index]['nights'] ?? 0;
            unset($this->segments[$index]);
            $this->segments = array_values($this->segments);

            if (count($this->segments) > 0) {
                $targetIndex = $index > 0 ? $index - 1 : 0;
                $this->segments[$targetIndex]['nights'] += $nightsToReturn;
            }

            $this->recalculateSegmentDates();
        }
    }

    public function addAccommodationToSegment($segmentIndex)
    {
        $seg = &$this->segments[$segmentIndex];
        $allocated = collect($seg['accommodations'])->sum('nights');
        $remaining = max(0, $seg['nights'] - $allocated);

        $defaultNights = 1;
        if ($remaining > 0) {
            $defaultNights = $remaining;
        } else {
            $lastAccIndex = count($seg['accommodations']) - 1;
            if ($lastAccIndex >= 0 && $seg['accommodations'][$lastAccIndex]['nights'] > 1) {
                $seg['accommodations'][$lastAccIndex]['nights']--;
                $defaultNights = 1;
            }
        }

        $seg['accommodations'][] = [
            'accommodation_id' => '',
            'buying_price' => 0,
            'nights' => $defaultNights,
            'note' => '',
        ];
    }

    public function removeAccommodationFromSegment($segmentIndex, $accIndex)
    {
        $seg = &$this->segments[$segmentIndex];
        if (count($seg['accommodations']) > 1) {
            $nightsToReturn = $seg['accommodations'][$accIndex]['nights'] ?? 0;
            unset($seg['accommodations'][$accIndex]);
            $seg['accommodations'] = array_values($seg['accommodations']);

            $targetIndex = $accIndex > 0 ? $accIndex - 1 : 0;
            $seg['accommodations'][$targetIndex]['nights'] += $nightsToReturn;
        }
    }

    public function updatedSegments($value, $key)
    {
        if (str_contains($key, '.nights')) {
            $parts = explode('.', $key);
            if (count($parts) === 2) {
                $this->recalculateSegmentDates();

                $segmentIndex = (int) $parts[1];
                if (count($this->segments[$segmentIndex]['accommodations']) === 1) {
                    $this->segments[$segmentIndex]['accommodations'][0]['nights'] = (int) $value;
                }
            }
        }

        if (str_contains($key, '.destinations')) {
            $parts = explode('.', $key);
            if (count($parts) === 2) {
                $segmentIndex = (int) $parts[1];
                $seg = &$this->segments[$segmentIndex];
                $validAccIds = Accommodation::whereIn('destination_id', $seg['destinations'] ?? [])->pluck('id')->toArray();

                $seg['accommodations'] = collect($seg['accommodations'] ?? [])
                    ->filter(function ($acc) use ($validAccIds) {
                        return empty($acc['accommodation_id']) || in_array($acc['accommodation_id'], $validAccIds);
                    })
                    ->values()
                    ->toArray();

                if (empty($seg['accommodations'])) {
                    $seg['accommodations'][] = ['accommodation_id' => '', 'buying_price' => 0, 'nights' => $seg['nights'], 'note' => ''];
                }

                $validTourIds = Tour::whereIn('destination_id', $seg['destinations'] ?? [])->pluck('id')->toArray();
                foreach ($seg['tours'] ?? [] as $tourIndex => $tour) {
                    if (! empty($tour['tour_id']) && ! in_array($tour['tour_id'], $validTourIds)) {
                        $seg['tours'][$tourIndex]['tour_id'] = '';
                        $seg['tours'][$tourIndex]['buying_price'] = 0;
                    }
                }
            }
        }

        if (str_contains($key, '.accommodation_id')) {
            $parts = explode('.', $key);
            if (count($parts) === 4) {
                $segmentIndex = (int) $parts[0];
                $accIndex = (int) $parts[2];
                if ($value) {
                    $acc = Accommodation::find($value);
                    if ($acc) {
                        $this->segments[$segmentIndex]['accommodations'][$accIndex]['buying_price'] = $acc->default_buying_price;
                    }
                }
            }
        }

        if (str_contains($key, '.tour_id')) {
            $parts = explode('.', $key);
            if (count($parts) === 4) {
                $segmentIndex = (int) $parts[0];
                $tourIndex = (int) $parts[2];
                if ($value) {
                    $tour = Tour::find($value);
                    if ($tour) {
                        $this->segments[$segmentIndex]['tours'][$tourIndex]['buying_price'] = $tour->default_buying_price;
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
                'adultsCount' => 'required|numeric|min:1',
                'childrenAges.*' => 'required|numeric|min:0|max:12',
                'arrivingDate' => 'required',
                'arrivingTime' => 'nullable|string',
                'leavingDate' => 'required',
                'leavingTime' => 'nullable|string',
            ]);
            if (empty($this->segments)) {
                $this->addSegment();
            }
        } elseif ($this->currentStep == 2) {
            $this->validate([
                'segments.*.destinations' => 'required|array|min:1',
                'segments.*.nights' => 'required|numeric|min:1',
                'segments.*.accommodations.*.accommodation_id' => 'required',
                'segments.*.accommodations.*.nights' => 'required|numeric|min:1',
                'segments.*.accommodations.*.buying_price' => 'required|numeric|min:0',
                'segments.*.accommodations.*.note' => 'nullable|string',
            ]);

            // Validate total nights
            $segTotalNights = collect($this->segments)->sum('nights');
            if ($segTotalNights > $this->totalNights) {
                $this->addError('segment_nights_total', 'إجمالي عدد ليالي الأقسام ('.$segTotalNights.') يتجاوز إجمالي ليالي الرحلة ('.$this->totalNights.').');

                return;
            }

            // Validate accommodation nights within each segment
            foreach ($this->segments as $index => $seg) {
                $accNights = collect($seg['accommodations'] ?? [])->sum('nights');
                if ($accNights > $seg['nights']) {
                    $this->addError('segments.'.$index.'.accommodation_nights', 'إجمالي ليالي الفنادق في هذا القسم ('.$accNights.') يتجاوز ليالي القسم ('.$seg['nights'].').');

                    return;
                }
            }
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
        foreach ($this->segments as $seg) {
            foreach ($seg['accommodations'] ?? [] as $acc) {
                $total += ((float) ($acc['buying_price'] ?? 0) * (int) ($acc['nights'] ?? 1));
            }
            if (! $this->includeRentalCar) {
                foreach ($seg['tours'] ?? [] as $tour) {
                    $total += (float) ($tour['buying_price'] ?? 0);
                }
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
        foreach ($this->segments as $seg) {
            if (! empty($seg['destinations'])) {
                $allDestIds = array_merge($allDestIds, $seg['destinations']);
            }
        }
        $this->destinations = array_values(array_unique($allDestIds));

        $dataToSave = [
            'adultsCount' => $this->adultsCount,
            'childrenAges' => $this->childrenAges,
            'arrivingTime' => $this->arrivingTime,
            'leavingTime' => $this->leavingTime,
            'includeRentalCar' => $this->includeRentalCar,
            'selectedCarId' => $this->selectedCarId,
            'carBuyingPrice' => $this->carBuyingPrice,
            'segments' => $this->segments,
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
        foreach ($this->segments as $seg) {
            if (! empty($seg['destinations'])) {
                $allDestIds = array_merge($allDestIds, $seg['destinations']);
            }
        }
        $this->destinations = array_values(array_unique($allDestIds));

        $dataToSave = [
            'adultsCount' => $this->adultsCount,
            'childrenAges' => $this->childrenAges,
            'arrivingTime' => $this->arrivingTime,
            'leavingTime' => $this->leavingTime,
            'includeRentalCar' => $this->includeRentalCar,
            'selectedCarId' => $this->selectedCarId,
            'carBuyingPrice' => $this->carBuyingPrice,
            'segments' => $this->segments,
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
            'segments' => $this->segments,
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

        $url = 'https://api.whatsapp.com/send?text='.urlencode($text);

        $this->dispatch('open-url', url: $url);
    }

    public function render()
    {
        return view('livewire.itinerary-generator', [
            'cars' => Car::all(),
            'dbDestinations' => Destination::all(),
        ])->layout('layouts.app');
    }
}
