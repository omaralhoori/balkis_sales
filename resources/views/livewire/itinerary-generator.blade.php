<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Progress Bar -->
    <div class="mb-8">
        <div class="flex items-center justify-between relative">
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 rounded-full z-0"></div>
            <div class="absolute right-0 top-1/2 transform -translate-y-1/2 h-1 bg-blue-600 rounded-full z-0 transition-all duration-500" style="width: {{ (($currentStep - 1) / 4) * 100 }}%"></div>
            
            @foreach(['البيانات', 'السكن', 'السيارة', 'الجدول', 'المراجعة'] as $index => $label)
            <div class="relative z-10 flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm {{ $currentStep > $index ? 'bg-blue-600 text-white' : ($currentStep == $index + 1 ? 'bg-blue-600 text-white ring-4 ring-blue-100' : 'bg-gray-200 text-gray-500') }} transition-all duration-300">
                    {{ $index + 1 }}
                </div>
                <span class="mt-2 text-xs font-medium text-gray-500">{{ $label }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
        
        <!-- Step 1: Info -->
        @if($currentStep == 1)
        <div class="p-8 animate-[fadeIn_0.3s_ease-out]">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">بيانات العميل والرحلة</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم العميل</label>
                    <input type="text" wire:model="customerName" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="محمد أحمد...">
                    @error('customerName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عدد البالغين</label>
                    <input type="number" wire:model="adultsCount" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" min="1">
                    @error('adultsCount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-sm font-medium text-gray-700">الأطفال وأعمارهم</label>
                        <button type="button" wire:click="addChild" class="text-blue-600 text-sm font-medium hover:underline">+ إضافة طفل</button>
                    </div>
                    @if(count($childrenAges) > 0)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                            @foreach($childrenAges as $index => $age)
                            <div class="relative flex items-center">
                                <input type="number" wire:model="childrenAges.{{ $index }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-8" placeholder="العمر" min="0" max="17">
                                <button type="button" wire:click="removeChild({{ $index }})" class="absolute right-2 text-red-500 hover:text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        @error('childrenAges.*') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    @else
                        <div class="text-sm text-gray-400">لا يوجد أطفال مضافين.</div>
                    @endif
                </div>

                <div class="md:col-span-2" wire:ignore>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الوجهات (المدن)</label>
                    <select multiple wire:model="destinations" x-data x-init="new TomSelect($el, {plugins: ['remove_button']})" class="w-full">
                        <option value="">اختر الوجهات...</option>
                        @foreach($dbDestinations as $dest)
                            <option value="{{ $dest->id }}">{{ $dest->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الوصول</label>
                    <input type="text" x-data x-init="flatpickr($el, {dateFormat: 'd-m-Y'})" wire:model.live="arrivingDate" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-left" dir="ltr" placeholder="DD-MM-YYYY">
                    @error('arrivingDate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ المغادرة</label>
                    <input type="text" x-data x-init="flatpickr($el, {dateFormat: 'd-m-Y'})" wire:model.live="leavingDate" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-left" dir="ltr" placeholder="DD-MM-YYYY">
                    @error('leavingDate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-6 bg-blue-50 p-4 rounded-xl flex items-center justify-between border border-blue-100">
                <div class="text-blue-800 font-medium">المدة الإجمالية:</div>
                <div class="text-blue-900 font-bold text-lg">{{ $totalDays }} أيام / {{ $totalNights }} ليالي</div>
            </div>
        </div>
        @endif

        <!-- Step 2: Accommodations -->
        @if($currentStep == 2)
        <div class="p-8 animate-[fadeIn_0.3s_ease-out]">
            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <h2 class="text-2xl font-bold text-gray-800">أماكن الإقامة (الفنادق)</h2>
                <button wire:click="addAccommodation" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg font-medium hover:bg-blue-200 transition-colors text-sm">
                    + إضافة سكن
                </button>
            </div>
            @error('accommodation_nights')
                <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm border border-red-200">
                    {{ $message }}
                </div>
            @enderror

            @foreach($selectedAccommodations as $index => $acc)
            <div class="bg-gray-50 p-5 rounded-xl mb-4 border border-gray-200 relative">
                @if(count($selectedAccommodations) > 1)
                <button wire:click="removeAccommodation({{ $index }})" class="absolute top-4 left-4 text-red-500 hover:text-red-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
                @endif
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2" wire:ignore>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اختر السكن</label>
                        <select wire:model.live="selectedAccommodations.{{ $index }}.accommodation_id" x-data x-init="new TomSelect($el, {create: false})" class="w-full">
                            <option value="">-- يرجى الاختيار --</option>
                            @foreach($accommodations as $accommodation)
                                <option value="{{ $accommodation->id }}">{{ $accommodation->name }} ({{ $accommodation->type }})</option>
                            @endforeach
                        </select>
                        @error('selectedAccommodations.'.$index.'.accommodation_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">عدد الليالي</label>
                        <input type="number" wire:model.live="selectedAccommodations.{{ $index }}.nights" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" min="1">
                        @error('selectedAccommodations.'.$index.'.nights') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-2">
                        <div class="w-1/2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">شراء ($)</label>
                            <input type="number" step="0.01" wire:model.live="selectedAccommodations.{{ $index }}.buying_price" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-gray-100">
                        </div>
                        <div class="w-1/2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">بيع ($)</label>
                            <input type="number" step="0.01" wire:model.live="selectedAccommodations.{{ $index }}.selling_price" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-semibold text-green-600">
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            
            @if(empty($selectedAccommodations))
                <div class="text-center py-8 text-gray-500">لم يتم إضافة أي سكن بعد.</div>
            @endif
        </div>
        @endif

        <!-- Step 3: Car -->
        @if($currentStep == 3)
        <div class="p-8 animate-[fadeIn_0.3s_ease-out]">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">استئجار سيارة</h2>
            
            <label class="flex items-center cursor-pointer mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                <div class="relative">
                    <input type="checkbox" wire:model.live="includeRentalCar" class="sr-only">
                    <div class="block bg-gray-300 w-14 h-8 rounded-full transition-colors {{ $includeRentalCar ? 'bg-blue-500' : '' }}"></div>
                    <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition transform {{ $includeRentalCar ? 'translate-x-6' : '' }}"></div>
                </div>
                <div class="mr-4 text-gray-700 font-medium">
                    تضمين سيارة سياحية في البرنامج؟
                </div>
            </label>

            @if($includeRentalCar)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-blue-50 p-6 rounded-xl border border-blue-100 animate-[fadeIn_0.3s_ease-out]">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">نوع السيارة</label>
                    <select wire:model.live="selectedCarId" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- يرجى الاختيار --</option>
                        @foreach($cars as $car)
                            <option value="{{ $car->id }}">{{ $car->car_type }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">سعر الشراء لليوم ($)</label>
                    <input type="number" step="0.01" wire:model.live="carBuyingPrice" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100 text-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">سعر البيع لليوم ($)</label>
                    <input type="number" step="0.01" wire:model.live="carSellingPrice" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-semibold text-green-600">
                </div>
                
                <div class="md:col-span-2 text-sm text-blue-700">
                    * سيتم حساب تكلفة السيارة لعدد <strong>{{ $totalDays }} أيام</strong> الإجمالية.
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Step 4: Daily Itinerary -->
        @if($currentStep == 4)
        <div class="p-8 animate-[fadeIn_0.3s_ease-out]">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">البرنامج السياحي اليومي</h2>
            
            <div class="space-y-4">
                @for($i = 1; $i <= $totalDays; $i++)
                <div class="bg-white border {{ isset($dailyTours[$i]['tour_id']) && $dailyTours[$i]['tour_id'] ? 'border-green-300 shadow-sm' : 'border-gray-200' }} p-4 rounded-xl">
                    <div class="flex flex-col md:flex-row md:items-center gap-4">
                        <div class="w-full md:w-1/4">
                            <div class="font-bold text-blue-800">اليوم {{ $i }}</div>
                            <div class="text-sm text-gray-500">{{ $dailyTours[$i]['date'] }}</div>
                        </div>
                        
                        <div class="w-full md:w-1/2">
                            <select wire:model.live="dailyTours.{{ $i }}.tour_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">-- اختر جولة سياحية (اختياري) --</option>
                                @foreach($tours as $tour)
                                    <option value="{{ $tour->id }}">{{ $tour->name }} ({{ $tour->type }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full md:w-1/4 flex gap-2">
                            <div class="w-1/2">
                                <label class="text-xs text-gray-500 mb-1 block">شراء ($)</label>
                                <input type="number" step="0.01" wire:model.live="dailyTours.{{ $i }}.buying_price" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-gray-100" {{ empty($dailyTours[$i]['tour_id']) ? 'disabled' : '' }}>
                            </div>
                            <div class="w-1/2">
                                <label class="text-xs text-gray-500 mb-1 block">بيع ($)</label>
                                <input type="number" step="0.01" wire:model.live="dailyTours.{{ $i }}.selling_price" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-semibold text-green-600" {{ empty($dailyTours[$i]['tour_id']) ? 'disabled' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>
                @endfor
            </div>
        </div>
        @endif

        <!-- Step 5: Review & Submit -->
        @if($currentStep == 5)
        <div class="p-8 animate-[fadeIn_0.3s_ease-out]">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-500 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">مراجعة الرحلة والتأكيد</h2>
                <p class="text-gray-500 mt-2">يرجى التأكد من الأسعار والتفاصيل قبل إصدار القسيمة</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 mb-6">
                <h3 class="font-bold text-lg border-b pb-2 mb-4 text-gray-700">ملخص التكاليف (سعر البيع النهائي)</h3>
                
                <div class="space-y-3">
                    @php $accTotal = 0; @endphp
                    @foreach($selectedAccommodations as $acc)
                        @if($acc['accommodation_id'])
                            @php 
                                $accModel = $accommodations->find($acc['accommodation_id']);
                                $lineTotal = $acc['selling_price'] * $acc['nights'];
                                $accTotal += $lineTotal;
                            @endphp
                            <div class="flex justify-between text-sm">
                                <span>سكن: {{ $accModel->name ?? '' }} ({{ $acc['nights'] }} ليالي)</span>
                                <span class="font-medium text-gray-800">${{ number_format($lineTotal, 2) }}</span>
                            </div>
                        @endif
                    @endforeach

                    @if($includeRentalCar && $selectedCarId)
                        @php 
                            $carModel = $cars->find($selectedCarId);
                            $carTotal = $carSellingPrice * $totalDays;
                        @endphp
                        <div class="flex justify-between text-sm border-t pt-3 border-gray-200">
                            <span>سيارة: {{ $carModel->car_type ?? '' }} ({{ $totalDays }} أيام)</span>
                            <span class="font-medium text-gray-800">${{ number_format($carTotal, 2) }}</span>
                        </div>
                    @endif

                    @php $toursTotal = 0; @endphp
                    @foreach($dailyTours as $day)
                        @if(!empty($day['tour_id']))
                            @php $toursTotal += $day['selling_price']; @endphp
                        @endif
                    @endforeach
                    
                    @if($toursTotal > 0)
                        <div class="flex justify-between text-sm border-t pt-3 border-gray-200">
                            <span>جولات سياحية (إجمالي)</span>
                            <span class="font-medium text-gray-800">${{ number_format($toursTotal, 2) }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center mt-6 pt-4 border-t-2 border-gray-300">
                    <span class="text-xl font-bold text-gray-900">الإجمالي الشامل</span>
                    <span class="text-2xl font-black text-green-600">${{ number_format($this->totalSellingPrice, 2) }}</span>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4">
                <button wire:click="downloadPdf" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-red-200 transition-all flex justify-center items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    تحميل PDF
                </button>
                <button wire:click="sendWhatsApp" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-green-200 transition-all flex justify-center items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                    إرسال WhatsApp
                </button>
            </div>
        </div>
        @endif

        <!-- Footer Actions -->
        <div class="px-8 py-5 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
            @if($currentStep > 1)
                <button wire:click="previousStep" class="text-gray-600 font-medium hover:text-gray-900 px-4 py-2 flex items-center transition-colors">
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    السابق
                </button>
            @else
                <div></div>
            @endif

            @if($currentStep < 5)
                <button wire:click="nextStep" class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-md shadow-blue-200 transition-all flex items-center">
                    التالي
                    <svg class="w-5 h-5 mr-1 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            @else
                <div></div>
            @endif
        </div>
    </div>
</div>
