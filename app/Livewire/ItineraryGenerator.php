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

    public int $totalDays = 0;

    public int $totalNights = 0;

    // Step 2: Accommodations
    public array $selectedAccommodations = [];

    // Step 3: Cars
    public bool $includeRentalCar = false;

    public ?int $selectedCarId = null;

    public float $carBuyingPrice = 0;

    public float $finalSellingPrice = 0;

    public bool $isPinned = false;

    public ?float $deposit = null;

    // Step 4: Daily Itinerary
    public array $dailyTours = [];

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
                $this->selectedAccommodations = $data['selectedAccommodations'] ?? [];
                $this->includeRentalCar = $data['includeRentalCar'] ?? false;
                $this->selectedCarId = $data['selectedCarId'] ?? null;
                $this->carBuyingPrice = $data['carBuyingPrice'] ?? 0;
                $this->dailyTours = $data['dailyTours'] ?? [];
                $this->finalSellingPrice = $data['finalSellingPrice'] ?? 0;
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
                $this->initDailyTours();
            } catch (\Exception $e) {
                // Ignore parsing errors temporarily if user is typing
            }
        }
    }

    public function initDailyTours()
    {
        $newDailyTours = [];
        try {
            $start = Carbon::createFromFormat('d-m-Y', $this->arrivingDate)->startOfDay();
            for ($i = 1; $i <= $this->totalDays; $i++) {
                $newDailyTours[$i] = [
                    'tour_id' => $this->dailyTours[$i]['tour_id'] ?? '',
                    'buying_price' => $this->dailyTours[$i]['buying_price'] ?? 0,
                    'date' => $start->copy()->addDays($i - 1)->format('d-m-Y'),
                ];
            }
            $this->dailyTours = $newDailyTours;
        } catch (\Exception $e) {
            // Ignore if date format is invalid
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

    public function addAccommodation()
    {
        $this->selectedAccommodations[] = ['accommodation_id' => '', 'buying_price' => 0, 'nights' => 1, 'note' => ''];
    }

    public function removeAccommodation($index)
    {
        unset($this->selectedAccommodations[$index]);
        $this->selectedAccommodations = array_values($this->selectedAccommodations);
    }

    public function updatedSelectedAccommodations($value, $key)
    {
        if (str_ends_with($key, '.accommodation_id')) {
            $index = explode('.', $key)[0];
            if ($value) {
                $acc = Accommodation::find($value);
                if ($acc) {
                    $this->selectedAccommodations[$index]['buying_price'] = $acc->default_buying_price;
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

    public function updatedDailyTours($value, $key)
    {
        if (str_ends_with($key, '.tour_id')) {
            $dayIndex = explode('.', $key)[0];
            if ($value) {
                $tour = Tour::find($value);
                if ($tour) {
                    $this->dailyTours[$dayIndex]['buying_price'] = $tour->default_buying_price;
                }
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
            ]);
            if (empty($this->selectedAccommodations)) {
                $this->addAccommodation();
            }
        } elseif ($this->currentStep == 2) {
            $this->validate([
                'selectedAccommodations.*.accommodation_id' => 'required',
                'selectedAccommodations.*.nights' => 'required|numeric|min:1',
                'selectedAccommodations.*.buying_price' => 'required|numeric|min:0',
                'selectedAccommodations.*.note' => 'nullable|string',
            ]);

            // Validate total nights
            $accTotalNights = collect($this->selectedAccommodations)->sum('nights');
            if ($accTotalNights > $this->totalNights) {
                $this->addError('accommodation_nights', 'إجمالي عدد الليالي للفنادق ('.$accTotalNights.') يتجاوز إجمالي ليالي الرحلة ('.$this->totalNights.').');

                return;
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
        foreach ($this->selectedAccommodations as $acc) {
            $total += ((float) ($acc['buying_price'] ?? 0) * (int) ($acc['nights'] ?? 1));
        }
        if ($this->includeRentalCar) {
            $total += ((float) $this->carBuyingPrice * $this->totalDays);
        } else {
            foreach ($this->dailyTours as $day) {
                $total += (float) ($day['buying_price'] ?? 0);
            }
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

        $dataToSave = [
            'adultsCount' => $this->adultsCount,
            'childrenAges' => $this->childrenAges,
            'arrivingTime' => $this->arrivingTime,
            'selectedAccommodations' => $this->selectedAccommodations,
            'includeRentalCar' => $this->includeRentalCar,
            'selectedCarId' => $this->selectedCarId,
            'carBuyingPrice' => $this->carBuyingPrice,
            'dailyTours' => $this->dailyTours,
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

        $dataToSave = [
            'adultsCount' => $this->adultsCount,
            'childrenAges' => $this->childrenAges,
            'arrivingTime' => $this->arrivingTime,
            'selectedAccommodations' => $this->selectedAccommodations,
            'includeRentalCar' => $this->includeRentalCar,
            'selectedCarId' => $this->selectedCarId,
            'carBuyingPrice' => $this->carBuyingPrice,
            'dailyTours' => $this->dailyTours,
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

        $data = [
            'customerName' => $this->customerName,
            'adultsCount' => $this->adultsCount,
            'childrenAges' => $this->childrenAges,
            'destinations' => Destination::whereIn('id', $this->destinations)->pluck('name')->toArray(),
            'arrivingDate' => $this->arrivingDate,
            'arrivingTime' => $this->arrivingTime,
            'leavingDate' => $this->leavingDate,
            'totalDays' => $this->totalDays,
            'totalNights' => $this->totalNights,
            'selectedAccommodations' => $this->selectedAccommodations,
            'includeRentalCar' => $this->includeRentalCar,
            'selectedCarId' => $this->selectedCarId,
            'carBuyingPrice' => $this->carBuyingPrice,
            'dailyTours' => $this->dailyTours,
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
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->SetDirectionality('rtl');
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
        $text .= "*تاريخ المغادرة:* {$this->leavingDate}\n";
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

    public function updatedDestinations()
    {
        $validAccIds = Accommodation::whereIn('destination_id', $this->destinations)->pluck('id')->toArray();

        $this->selectedAccommodations = collect($this->selectedAccommodations)
            ->filter(function ($acc) use ($validAccIds) {
                return empty($acc['accommodation_id']) || in_array($acc['accommodation_id'], $validAccIds);
            })
            ->values()
            ->toArray();

        if (empty($this->selectedAccommodations) && $this->currentStep == 2) {
            $this->addAccommodation();
        }

        $validTourIds = Tour::whereIn('destination_id', $this->destinations)->pluck('id')->toArray();

        foreach ($this->dailyTours as $dayIndex => $day) {
            if (! empty($day['tour_id']) && ! in_array($day['tour_id'], $validTourIds)) {
                $this->dailyTours[$dayIndex]['tour_id'] = '';
                $this->dailyTours[$dayIndex]['buying_price'] = 0;
            }
        }
    }

    public function render()
    {
        $accommodations = Accommodation::query();
        $tours = Tour::query();

        if (! empty($this->destinations)) {
            $accommodations->whereIn('destination_id', $this->destinations);
            $tours->whereIn('destination_id', $this->destinations);
        } else {
            $accommodations->whereNull('id');
            $tours->whereNull('id');
        }

        return view('livewire.itinerary-generator', [
            'accommodations' => $accommodations->get(),
            'tours' => $tours->get(),
            'cars' => Car::all(),
            'dbDestinations' => Destination::all(),
        ])->layout('layouts.app');
    }
}
