<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-blue-900">منشئ البرامج السياحية</h1>
        <div class="flex gap-3">
            <a href="{{ route('itineraries.index') }}" class="bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-lg font-medium transition-colors border border-gray-300">
                طلباتي المحفوظة
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg font-medium transition-colors border border-red-200">
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>
    <!-- Progress Bar -->
    <div class="mb-8">
        <div class="flex items-center justify-between relative">
            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 rounded-full z-0"></div>
            <div class="absolute right-0 top-1/2 transform -translate-y-1/2 h-1 bg-blue-600 rounded-full z-0 transition-all duration-500" style="width: {{ (($currentStep - 1) / 2) * 100 }}%"></div>
            
            @foreach(['البيانات', 'تفاصيل الحجز', 'المراجعة'] as $index => $label)
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
        @if($isPinned)
            <div class="bg-red-50 border-r-4 border-red-500 p-4 m-6 rounded-xl flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-red-500 ml-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <p class="text-red-800 font-bold">هذا البرنامج السياحي مثبت ومغلق.</p>
                        <p class="text-red-700 text-sm mt-1">
                            {{ $this->isEditable ? 'أنت تتصفح بصفتك مسؤول النظام (Super Admin)، لذا يمكنك إجراء تعديلات وحفظها.' : 'لا يمكن التعديل عليه أو حفظ التغييرات إلا من خلال المدير العام.' }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Step 1: Info -->
        @if($currentStep == 1)
        <div class="p-8 animate-[fadeIn_0.3s_ease-out]">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">بيانات العميل والرحلة</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم العميل</label>
                    <input type="text" wire:model="customerName" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="محمد أحمد..." {{ !$this->isEditable ? 'disabled' : '' }}>
                    @error('customerName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-1">
    <label class="block text-sm font-medium text-gray-900 mb-1">واتساب العميل (اختياري)</label>
    <div class="flex">
    <select wire:model="countryCode" class="rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-700 text-sm focus:border-blue-500 focus:ring-blue-500">
        <option value="90">+90 (تركيا)</option>
        <option value="966">+966 (السعودية)</option>
        <option value="971">+971 (الإمارات)</option>
        <option value="965">+965 (الكويت)</option>
        <option value="974">+974 (قطر)</option>
        <option value="973">+973 (البحرين)</option>
        <option value="968">+968 (عمان)</option>
        <option value="20">+20 (مصر)</option>
        <option value="970">+970 (فلسطين)</option>
    </select>
    <input type="text" wire:model.lazy="customerWhatsapp" placeholder="رقم الهاتف (اختياري)" class="flex-1 rounded-r-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
</div>
    @error('customerWhatsapp') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
</div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عدد البالغين</label>
                    <input type="number" wire:model="adultsCount" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" min="1" {{ !$this->isEditable ? 'disabled' : '' }}>
                    @error('adultsCount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-sm font-medium text-gray-700">الأطفال وأعمارهم</label>
                        @if($this->isEditable)
                            <button type="button" wire:click="addChild" class="text-blue-600 text-sm font-medium hover:underline">+ إضافة طفل</button>
                        @endif
                    </div>
                    @if(count($childrenAges) > 0)
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                            @foreach($childrenAges as $index => $age)
                            <div class="relative flex items-center">
                                <input type="number" wire:model="childrenAges.{{ $index }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-8" placeholder="العمر" min="0" max="12" {{ !$this->isEditable ? 'disabled' : '' }}>
                                @if($this->isEditable)
                                    <button type="button" wire:click="removeChild({{ $index }})" class="absolute right-2 text-red-500 hover:text-red-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @error('childrenAges.*') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    @else
                        <div class="text-sm text-gray-400">لا يوجد أطفال مضافين.</div>
                    @endif
                </div>

                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الوصول</label>
                        <div wire:ignore>
                            <input type="text" 
                                   x-data="{ value: @entangle('arrivingDate').live }" 
                                   x-init="flatpickr($el, {
                                       dateFormat: 'd-m-Y', 
                                       allowInput: true,
                                       defaultDate: value,
                                       clickOpens: {{ $this->isEditable ? 'true' : 'false' }},
                                       onChange: function(selectedDates, dateStr) { value = dateStr; }
                                   })" 
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-left" 
                                   dir="ltr" 
                                   placeholder="DD-MM-YYYY"
                                   {{ !$this->isEditable ? 'disabled' : '' }}>
                        </div>
                        @error('arrivingDate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">وقت الوصول (اختياري)</label>
                        <input type="time" 
                               wire:model="arrivingTime" 
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-left" 
                               dir="ltr"
                               {{ !$this->isEditable ? 'disabled' : '' }}>
                        @error('arrivingTime') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ المغادرة</label>
                        <div wire:ignore>
                            <input type="text" 
                                   x-data="{ value: @entangle('leavingDate').live }" 
                                   x-init="flatpickr($el, {
                                       dateFormat: 'd-m-Y', 
                                       allowInput: true,
                                       defaultDate: value,
                                       clickOpens: {{ $this->isEditable ? 'true' : 'false' }},
                                       onChange: function(selectedDates, dateStr) { value = dateStr; }
                                   })" 
                                   class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-left" 
                                   dir="ltr" 
                                   placeholder="DD-MM-YYYY"
                                   {{ !$this->isEditable ? 'disabled' : '' }}>
                        </div>
                        @error('leavingDate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">وقت المغادرة (اختياري)</label>
                        <input type="time" 
                               wire:model="leavingTime" 
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-left" 
                               dir="ltr"
                               {{ !$this->isEditable ? 'disabled' : '' }}>
                        @error('leavingTime') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-blue-50 p-4 rounded-xl flex items-center justify-between border border-blue-100">
                <div class="text-blue-800 font-medium">المدة الإجمالية:</div>
                <div class="text-blue-900 font-bold text-lg">{{ $totalDays }} أيام / {{ $totalNights }} ليالي</div>
            </div>
        </div>
        @endif

        <!-- Step 2: Booking Details -->
        @if($currentStep == 2)
        <div class="p-8 animate-[fadeIn_0.3s_ease-out]">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">تفاصيل الحجز</h2>

            <!-- ملخص التواريخ والاشخاص في اعلى الصفحة -->
            <div class="bg-blue-50 rounded-xl p-5 border border-blue-200 mb-8">
                <h3 class="text-sm font-semibold text-blue-900 uppercase tracking-wider mb-3">ملخص الرحلة</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <div class="text-xs text-blue-600">اسم العميل</div>
                        <div class="font-bold text-gray-800">{{ $customerName }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-blue-600">تاريخ الوصول</div>
                        <div class="font-bold text-gray-800">
                            {{ $arrivingDate }} @if($arrivingTime) <span class="text-xs text-gray-500">({{ $arrivingTime }})</span> @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-blue-600">تاريخ المغادرة</div>
                        <div class="font-bold text-gray-800">
                            {{ $leavingDate }} @if($leavingTime) <span class="text-xs text-gray-500">({{ $leavingTime }})</span> @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-blue-600">المدة الإجمالية</div>
                        <div class="font-bold text-gray-800">{{ $totalDays }} أيام / {{ $totalNights }} ليالي</div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-blue-100 flex gap-6 text-sm text-gray-700">
                    <div>
                        <strong>البالغين:</strong> {{ $adultsCount }}
                    </div>
                    @if(count($childrenAges) > 0)
                        <div>
                            <strong>الأطفال:</strong> {{ count($childrenAges) }} <span class="text-xs text-gray-500">(أعمارهم: {{ implode('، ', $childrenAges) }})</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Validation Errors for Daily Slots -->
            @if ($errors->any())
                <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 text-sm border border-red-200">
                    <p class="font-bold mb-2">يرجى تصحيح الأخطاء التالية:</p>
                    <ul class="list-disc pr-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- الأيام والبرنامج اليومي -->
            <div class="space-y-8">
                <!-- أدوات الفرز والترتيب للسكن -->
                <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 flex flex-wrap gap-4 items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-700">فرز وترتيب خيارات الإقامة والسكن:</span>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <select wire:model.live="accTypeFilter" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs">
                            <option value="all">كل أنواع السكن</option>
                            <option value="فندق">فندق</option>
                            <option value="شقق فندقية">شقق فندقية</option>
                            <option value="كوخ">كوخ</option>
                            <option value="فيلا">فيلا</option>
                        </select>
                        <select wire:model.live="accStarsFilter" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs">
                            <option value="all">كل التصنيفات (عدد النجوم)</option>
                            <option value="5">★★★★★ (5 نجوم)</option>
                            <option value="4">★★★★ (4 نجوم)</option>
                            <option value="3">★★★ (3 نجوم)</option>
                            <option value="2">★★ (2 نجمة)</option>
                            <option value="1">★ (1 نجمة)</option>
                            <option value="others">إقامات أخرى (بدون تصنيف / غير فندق)</option>
                        </select>
                        <select wire:model.live="accSortBy" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs">
                            <option value="name">ترتيب أبجدياً (حسب الاسم)</option>
                            <option value="price">ترتيب حسب السعر (من سعر الشراء)</option>
                        </select>
                        <select wire:model.live="accSortOrder" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs">
                            <option value="asc">تصاعدي (من الأقل للأعلى / أ-ي)</option>
                            <option value="desc">تنازلي (من الأعلى للأقل / ي-أ)</option>
                        </select>
                    </div>
                </div>

                @foreach($dailySlots as $slotIndex => $slot)
                <div class="bg-white border-2 border-blue-100 rounded-2xl p-6 shadow-sm relative">
                    <h3 class="text-lg font-bold text-blue-900 mb-6 flex items-center justify-between gap-2 border-b pb-4">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white w-6 h-6 rounded-full inline-flex items-center justify-center text-xs font-black">{{ $slotIndex + 1 }}</span>
                            اليوم {{ $slotIndex + 1 }}
                        </span>
                        <span class="text-sm font-medium text-gray-500">{{ $slot['date'] }}</span>
                    </h3>

                    <div class="grid grid-cols-1 {{ !$includeRentalCar ? 'md:grid-cols-2' : '' }} gap-8">
                        
                        <!-- Accommodation Slot (except for departure day) -->
                        @if($slotIndex < $totalNights)
                        <div class="space-y-4">
                            <h4 class="font-bold text-gray-800 border-r-4 border-blue-600 pr-2">تفاصيل الإقامة والسكن (الليلة {{ $slotIndex + 1 }})</h4>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">الوجهة (المدينة للسكن)</label>
                                <select wire:model.live="dailySlots.{{ $slotIndex }}.accommodation.destination_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" {{ !$this->isEditable ? 'disabled' : '' }}>
                                    <option value="">-- اختر الوجهة للسكن --</option>
                                    @foreach($dbDestinations as $dest)
                                        <option value="{{ $dest->id }}">{{ $dest->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @php
                                $accDestId = $slot['accommodation']['destination_id'] ?? '';
                                $filteredAccs = !empty($accDestId) ? \App\Models\Accommodation::where('destination_id', $accDestId)->get() : collect();

                                // Sort accommodations
                                if ($accSortBy === 'price') {
                                    $sortedAccs = $filteredAccs->sortBy('default_buying_price', SORT_REGULAR, $accSortOrder === 'desc');
                                } else {
                                    $sortedAccs = $filteredAccs->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE, $accSortOrder === 'desc');
                                }

                                // Filter by type if applicable
                                if ($accTypeFilter !== 'all') {
                                    $sortedAccs = $sortedAccs->filter(fn($item) => $item->type === $accTypeFilter);
                                }

                                // Filter by stars if applicable
                                if ($accStarsFilter !== 'all') {
                                    if ($accStarsFilter === 'others') {
                                        $sortedAccs = $sortedAccs->filter(fn($item) => $item->type !== 'فندق' || empty($item->stars));
                                    } else {
                                        $sortedAccs = $sortedAccs->filter(fn($item) => $item->type === 'فندق' && (int)$item->stars === (int)$accStarsFilter);
                                    }
                                }

                                // Group accommodations by stars (for hotels) or as "Others"
                                $groupedAccOptions = [];
                                
                                // Group hotels by star rating: 5 down to 1
                                for ($stars = 5; $stars >= 1; $stars--) {
                                    $items = $sortedAccs->filter(fn($item) => $item->type === 'فندق' && (int)$item->stars === $stars);
                                    if ($items->isNotEmpty()) {
                                        $groupedAccOptions[] = [
                                            'label' => str_repeat('★', $stars) . " ({$stars} نجوم)",
                                            'items' => $items->map(fn($item) => [
                                                'id' => $item->id,
                                                'name' => $item->name . ' ($' . number_format($item->default_buying_price) . ')',
                                            ])->values()->toArray(),
                                        ];
                                    }
                                }
                                
                                // Group all other accommodations (non-hotels, or hotels without stars)
                                $others = $sortedAccs->filter(fn($item) => $item->type !== 'فندق' || empty($item->stars));
                                if ($others->isNotEmpty()) {
                                    $groupedAccOptions[] = [
                                        'label' => 'إقامات أخرى',
                                        'items' => $others->map(fn($item) => [
                                            'id' => $item->id,
                                            'name' => $item->name . ' (' . ($item->type ?? 'غير محدد') . ') ($' . number_format($item->default_buying_price) . ')',
                                        ])->values()->toArray(),
                                    ];
                                }

                                $selectedAccModel = !empty($slot['accommodation']['accommodation_id']) ? $filteredAccs->firstWhere('id', $slot['accommodation']['accommodation_id']) : null;
                                $selectedLabel = $selectedAccModel ? $selectedAccModel->name . ' (' . $selectedAccModel->type . ')' : '-- اختر السكن --';
                            @endphp
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">اختر السكن</label>
                                <div x-data="{
                                    open: false,
                                    search: '',
                                    groups: {{ json_encode($groupedAccOptions, JSON_UNESCAPED_UNICODE) }},
                                    get filteredGroups() {
                                        if (!this.search) return this.groups;
                                        return this.groups.map(g => ({
                                            label: g.label,
                                            items: g.items.filter(i => i.name.toLowerCase().includes(this.search.toLowerCase()))
                                        })).filter(g => g.items.length > 0);
                                    },
                                    get hasMatches() {
                                        return this.filteredGroups.length > 0;
                                    }
                                }" class="relative" wire:key="acc-select-{{ $slotIndex }}-{{ $slot['accommodation']['accommodation_id'] ?? '' }}-{{ $accDestId }}-{{ $accSortBy }}-{{ $accSortOrder }}-{{ $accStarsFilter }}-{{ $accTypeFilter }}">
                                    <button type="button" @click="open = !open" {{ !$this->isEditable || empty($accDestId) ? 'disabled' : '' }} class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-right cursor-default focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 flex justify-between items-center text-sm disabled:bg-gray-100 disabled:text-gray-500">
                                        <span>{{ empty($accDestId) ? '-- يرجى اختيار الوجهة أولاً --' : $selectedLabel }}</span>
                                        <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    
                                    <div x-show="open" @click.outside="open = false" x-cloak class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                        <div class="p-2 sticky top-0 bg-white border-b">
                                            <input type="text" x-model="search" placeholder="ابحث عن سكن..." class="w-full px-3 py-1 text-sm border rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        <div class="max-h-48 overflow-y-auto">
                                            <template x-for="group in filteredGroups" :key="group.label">
                                                <div>
                                                    <div class="px-3 py-1 text-xs font-bold text-gray-500 bg-gray-100" x-text="group.label"></div>
                                                    <template x-for="item in group.items" :key="item.id">
                                                        <div @click="$wire.set('dailySlots.{{ $slotIndex }}.accommodation.accommodation_id', item.id, true); open = false; search = '';" 
                                                             class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-blue-600 hover:text-white text-gray-900"
                                                             :class="{'bg-blue-50 font-semibold': '{{ $slot['accommodation']['accommodation_id'] ?? '' }}' == item.id}">
                                                            <span class="block truncate" x-text="item.name"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <div x-show="!hasMatches" class="text-gray-500 text-sm p-3 text-center">لا توجد نتائج</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($selectedAccModel)
                                @php
                                    $accType = $selectedAccModel->type;
                                    $optionsList = [];
                                    if ($accType === 'فندق') {
                                        $optionsList = ['لـ شخص واحد', 'لـ شخصين', 'لـ 3 أشخاص', 'أخرى'];
                                    } elseif ($accType === 'شقق فندقية' || $accType === 'شقة فندقية') {
                                        $optionsList = ['غرفة وصالة', '2 غرفة وصالة', '3 غرفة وصالة', 'أخرى'];
                                    } elseif ($accType === 'كوخ') {
                                        $optionsList = ['لـ شخصين', 'لـ 3 أشخاص', 'لـ 4 أشخاص', 'لـ 5 أشخاص', 'لـ 6 أشخاص', 'لـ 7 أشخاص', 'لـ 8 أشخاص', 'أخرى'];
                                    } elseif ($accType === 'فيلا') {
                                        $optionsList = ['طابق واحد', 'طابقين', 'عدد الأشخاص'];
                                    }
                                @endphp

                                @if(!empty($optionsList))
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">تفاصيل نوع الإقامة</label>
                                            <select wire:model.live="dailySlots.{{ $slotIndex }}.accommodation.room_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" {{ !$this->isEditable ? 'disabled' : '' }}>
                                                <option value="">-- اختر خيار الإقامة --</option>
                                                @foreach($optionsList as $opt)
                                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if(($slot['accommodation']['room_type'] ?? '') === 'أخرى' || ($slot['accommodation']['room_type'] ?? '') === 'عدد الأشخاص')
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 mb-1">
                                                    {{ ($slot['accommodation']['room_type'] ?? '') === 'عدد الأشخاص' ? 'عدد الأشخاص (كتابة يدوية)' : 'تفاصيل أخرى (كتابة يدوية)' }}
                                                </label>
                                                <input type="text" wire:model="dailySlots.{{ $slotIndex }}.accommodation.custom_room_type" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="{{ ($slot['accommodation']['room_type'] ?? '') === 'عدد الأشخاص' ? 'مثال: 5 أشخاص' : 'اكتب التفاصيل هنا...' }}" {{ !$this->isEditable ? 'disabled' : '' }}>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endif

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">شراء السكن ($)</label>
                                    <input type="number" step="0.01" wire:model.live="dailySlots.{{ $slotIndex }}.accommodation.buying_price" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-gray-50" {{ !$this->isEditable || empty($slot['accommodation']['accommodation_id']) ? 'disabled' : '' }}>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">ملاحظة</label>
                                    <input type="text" wire:model="dailySlots.{{ $slotIndex }}.accommodation.note" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="اختياري" {{ !$this->isEditable || empty($slot['accommodation']['accommodation_id']) ? 'disabled' : '' }}>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="flex flex-col justify-center items-center p-6 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                            <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            <span class="text-sm font-semibold text-gray-500 text-center">يوم المغادرة (لا يوجد مبيت سكن في هذه الليلة)</span>
                        </div>
                        @endif

                        <!-- Tour Slot (only if rental car is disabled or excluded for this day) -->
                        @if(!$includeRentalCar || ($slotIndex == 0 && $excludeCarFirstDay) || ($slotIndex == $totalDays - 1 && $excludeCarLastDay))
                        <div class="space-y-4">
                            <h4 class="font-bold text-gray-800 border-r-4 border-amber-500 pr-2">تفاصيل الرحلة السياحية والبرنامج</h4>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">الوجهة (المدينة للرحلة)</label>
                                <select wire:model.live="dailySlots.{{ $slotIndex }}.tour.destination_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" {{ !$this->isEditable ? 'disabled' : '' }}>
                                    <option value="">-- اختر الوجهة للرحلة --</option>
                                    @foreach($dbDestinations as $dest)
                                        <option value="{{ $dest->id }}">{{ $dest->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @php
                                $tourDestId = $slot['tour']['destination_id'] ?? '';
                                $filteredTours = !empty($tourDestId) ? \App\Models\Tour::where('destination_id', $tourDestId)->get() : collect();
                                $tourOptions = $filteredTours->map(fn($item) => [
                                    'id' => $item->id,
                                    'name' => $item->name . ' (' . $item->type . ')',
                                ])->toArray();
                                $selectedTourModel = !empty($slot['tour']['tour_id']) ? $filteredTours->firstWhere('id', $slot['tour']['tour_id']) : null;
                                $selectedTourLabel = $selectedTourModel ? $selectedTourModel->name . ' (' . $selectedTourModel->type . ')' : '-- اختر جولة سياحية (اختياري) --';
                            @endphp
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">اختر جولة سياحية</label>
                                <div x-data="{
                                    open: false,
                                    search: '',
                                    options: {{ json_encode($tourOptions) }},
                                    get hasMatches() {
                                        if (!this.search) return true;
                                        return this.options.some(o => o.name.toLowerCase().includes(this.search.toLowerCase()));
                                    }
                                }" class="relative" wire:key="tour-select-{{ $slotIndex }}-{{ $slot['tour']['tour_id'] ?? '' }}-{{ $tourDestId }}">
                                    <button type="button" @click="open = !open" {{ !$this->isEditable || empty($tourDestId) ? 'disabled' : '' }} class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-3 py-2 text-right cursor-default focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 flex justify-between items-center text-sm disabled:bg-gray-100 disabled:text-gray-500">
                                        <span>{{ empty($tourDestId) ? '-- يرجى اختيار الوجهة أولاً --' : $selectedTourLabel }}</span>
                                        <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    
                                    <div x-show="open" @click.outside="open = false" x-cloak class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                        <div class="p-2 sticky top-0 bg-white border-b">
                                            <input type="text" x-model="search" placeholder="ابحث عن جولة..." class="w-full px-3 py-1 text-sm border rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        <div class="max-h-48 overflow-y-auto">
                                            @foreach($tourOptions as $option)
                                                <div @click="$wire.set('dailySlots.{{ $slotIndex }}.tour.tour_id', '{{ $option['id'] }}', true); open = false; search = '';" 
                                                     x-show="!search || '{{ strtolower(addslashes($option['name'])) }}'.includes(search.toLowerCase())"
                                                     class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-blue-600 hover:text-white text-gray-900 {{ ($slot['tour']['tour_id'] ?? '') == $option['id'] ? 'bg-blue-50 font-semibold' : '' }}">
                                                    <span class="block truncate">{{ $option['name'] }}</span>
                                                </div>
                                            @endforeach
                                            <div x-show="!hasMatches" class="text-gray-500 text-sm p-3 text-center">لا توجد نتائج</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">شراء الرحلة ($)</label>
                                <input type="number" step="0.01" wire:model.live="dailySlots.{{ $slotIndex }}.tour.buying_price" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-gray-50" {{ !$this->isEditable || empty($slot['tour']['tour_id']) ? 'disabled' : '' }}>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
                @endforeach
            </div>

            <!-- السيارة والبرنامج السياحي -->
            <div class="mt-8 border-t pt-8">
                <h3 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b">السيارة والبرنامج السياحي</h3>
                
                <label class="flex items-center mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200 {{ $this->isEditable ? 'cursor-pointer hover:bg-gray-100' : 'opacity-60' }} transition-colors">
                    <div class="relative">
                        <input type="checkbox" wire:model.live="includeRentalCar" class="sr-only" {{ !$this->isEditable ? 'disabled' : '' }}>
                        <div class="block bg-gray-300 w-14 h-8 rounded-full transition-colors {{ $includeRentalCar ? 'bg-blue-500' : '' }}"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition transform {{ $includeRentalCar ? 'translate-x-6' : '' }}"></div>
                    </div>
                    <div class="mr-4 text-gray-700 font-medium">
                        العميل يريد سيارة سياحية بدون سائق؟ (إذا تم التحديد، سيلغى اختيار الرحلات اليومية)
                    </div>
                </label>

                @if($includeRentalCar)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-blue-50 p-6 rounded-xl border border-blue-100 animate-[fadeIn_0.3s_ease-out]">
                    <!-- أدوات الفرز والترتيب للسيارات -->
                    <div class="md:col-span-2 flex flex-wrap gap-4 items-center justify-between border-b pb-4 mb-2">
                        <span class="text-sm font-semibold text-blue-900 flex items-center gap-1">
                            <svg class="w-4 h-4 text-blue-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path>
                            </svg>
                            ترتيب خيارات السيارات:
                        </span>
                        <div class="flex gap-2">
                            <select wire:model.live="carSortBy" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs" {{ !$this->isEditable ? 'disabled' : '' }}>
                                <option value="name">أبجدياً (حسب الاسم)</option>
                                <option value="price">حسب السعر</option>
                            </select>
                            <select wire:model.live="carSortOrder" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs" {{ !$this->isEditable ? 'disabled' : '' }}>
                                <option value="asc">من الأقل للأعلى</option>
                                <option value="desc">من الأعلى للأقل</option>
                            </select>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">نوع السيارة</label>
                        <select wire:model.live="selectedCarId" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" {{ !$this->isEditable ? 'disabled' : '' }}>
                            <option value="">-- يرجى الاختيار --</option>
                            @foreach($cars as $car)
                                <option value="{{ $car->id }}">{{ $car->car_type }} (${{ number_format($car->default_buying_price) }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">سعر الشراء لليوم ($)</label>
                        <input type="number" step="0.01" wire:model.live="carBuyingPrice" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100 text-gray-600" {{ !$this->isEditable ? 'disabled' : '' }}>
                    </div>

                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                        <label class="flex items-center cursor-pointer select-none">
                            <input type="checkbox" wire:model.live="excludeCarFirstDay" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ !$this->isEditable ? 'disabled' : '' }}>
                            <span class="mr-2 text-sm text-gray-700 font-medium">عدم إضافة السيارة لليوم الأول</span>
                        </label>
                        <label class="flex items-center cursor-pointer select-none">
                            <input type="checkbox" wire:model.live="excludeCarLastDay" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ !$this->isEditable ? 'disabled' : '' }}>
                            <span class="mr-2 text-sm text-gray-700 font-medium">عدم إضافة السيارة لليوم الأخير</span>
                        </label>
                    </div>
                    
                    <div class="md:col-span-2 text-sm text-blue-700">
                        * سيتم حساب تكلفة السيارة لعدد <strong>{{ max(0, $totalDays - ($excludeCarFirstDay ? 1 : 0) - ($excludeCarLastDay ? 1 : 0)) }} أيام</strong> الإجمالية.
                    </div>
                </div>
                @endif
            </div>

            <!-- ملاحظة اسفل صفحة تفاصيل الحجز تظهر اسفل ملف voucher -->
            <div class="mt-8 border-t pt-8">
                <h3 class="text-xl font-bold text-gray-800 mb-2">ملاحظات إضافية (تظهر في الفاتورة)</h3>
                <p class="text-sm text-gray-500 mb-3">تظهر هذه الملاحظات في نهاية ملف voucher المطبوع.</p>
                <textarea wire:model="voucherNotes" rows="4" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="أدخل أي ملاحظات ترغب في ظهورها بأسفل الفاتورة للعميل..." {{ !$this->isEditable ? 'disabled' : '' }}></textarea>
            </div>
        </div>
        @endif

        <!-- Step 3: Review & Submit -->
        @if($currentStep == 3)
        <div class="p-8 animate-[fadeIn_0.3s_ease-out]">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-500 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">مراجعة الرحلة والتأكيد</h2>
                <p class="text-gray-500 mt-2">يرجى التأكد من الأسعار والتفاصيل قبل إصدار القسيمة</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 mb-6">
                <h3 class="font-bold text-lg border-b pb-2 mb-4 text-gray-700">ملخص إجمالي الشراء الداخلي</h3>
                
                <div class="space-y-3">
                    @foreach($dailySlots as $index => $slot)
                        @if($index < $totalNights && !empty($slot['accommodation']['accommodation_id']))
                            @php 
                                $accModel = \App\Models\Accommodation::find($slot['accommodation']['accommodation_id']);
                                $accPrice = $slot['accommodation']['buying_price'] ?? 0;
                            @endphp
                            <div class="flex justify-between text-sm text-gray-500">
                                <span>سكن: {{ $accModel->name ?? '' }} (اليوم {{ $index + 1 }} - ليلة {{ $index + 1 }})</span>
                                <span>${{ number_format($accPrice, 2) }}</span>
                            </div>
                        @endif

                        @php 
                            $hasNoCarOnThisDay = !$includeRentalCar || ($index == 0 && $excludeCarFirstDay) || ($index == $totalDays - 1 && $excludeCarLastDay);
                        @endphp
                        @if($hasNoCarOnThisDay && !empty($slot['tour']['tour_id']))
                            @php 
                                $tourModel = \App\Models\Tour::find($slot['tour']['tour_id']);
                                $tourPrice = $slot['tour']['buying_price'] ?? 0;
                            @endphp
                            <div class="flex justify-between text-sm text-gray-500">
                                <span>جولة: {{ $tourModel->name ?? '' }} (اليوم {{ $index + 1 }} - {{ $slot['date'] }})</span>
                                <span>${{ number_format($tourPrice, 2) }}</span>
                            </div>
                        @endif
                    @endforeach

                    @if($includeRentalCar && $selectedCarId)
                        @php 
                            $carModel = $cars->find($selectedCarId);
                            $carDays = $totalDays - ($excludeCarFirstDay ? 1 : 0) - ($excludeCarLastDay ? 1 : 0);
                            $carTotal = $carBuyingPrice * max(0, $carDays);
                            $exclusions = [];
                            if ($excludeCarFirstDay) $exclusions[] = 'اليوم الأول';
                            if ($excludeCarLastDay) $exclusions[] = 'اليوم الأخير';
                            $exclusionsStr = !empty($exclusions) ? ' (باستثناء ' . implode(' و ', $exclusions) . ')' : '';
                        @endphp
                        <div class="flex justify-between text-sm text-gray-500 border-t pt-3 border-gray-200">
                            <span>سيارة بدون سائق: {{ $carModel->car_type ?? '' }} ({{ max(0, $carDays) }} أيام{{ $exclusionsStr }})</span>
                            <span>${{ number_format($carTotal, 2) }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center mt-6 pt-4 border-t-2 border-gray-300">
                    <span class="text-xl font-bold text-gray-700">إجمالي سعر الشراء:</span>
                    <span class="text-xl font-bold text-gray-700">${{ number_format($this->totalBuyingPrice, 2) }}</span>
                </div>
            </div>
            
            <div
                class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6"
                x-data="{
                    price: {{ (float) $finalSellingPrice }},
                    deposit: {{ (float) ($deposit ?? 0) }},
                    get remaining() {
                        return Math.max(0, this.price - this.deposit);
                    }
                }"
            >
                <div class="bg-blue-50 rounded-xl p-6 border border-blue-200">
                    <label class="block text-lg font-bold text-blue-900 mb-2">سعر المبيع الإجمالي لكامل الحزمة ($)</label>
                    <p class="text-sm text-blue-700 mb-3">هذا هو السعر الذي سيظهر للعميل في قسيمة الحجز النهائية.</p>
                    <input
                        type="number"
                        step="0.01"
                        x-model="price"
                        x-on:blur="$wire.set('finalSellingPrice', price)"
                        class="w-full rounded-xl border-blue-300 shadow-sm focus:border-blue-600 focus:ring-blue-600 text-2xl font-black text-green-700 text-center py-4"
                        placeholder="مثال: 1500.00"
                        {{ !$this->isEditable ? 'disabled' : '' }}
                    >
                    @error('finalSellingPrice') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="bg-amber-50 rounded-xl p-6 border border-amber-200">
                    <label class="block text-lg font-bold text-amber-900 mb-2">العربون المدفوع (اختياري) ($)</label>
                    <p class="text-sm text-amber-700 mb-3">
                        المبلغ المتبقي:
                        <strong class="text-xl text-blue-800" x-text="'$' + remaining.toFixed(2)"></strong>
                    </p>
                    <input
                        type="number"
                        step="0.01"
                        x-model="deposit"
                        x-on:blur="$wire.set('deposit', deposit)"
                        class="w-full rounded-xl border-amber-300 shadow-sm focus:border-amber-600 focus:ring-amber-600 text-2xl font-black text-amber-700 text-center py-4"
                        placeholder="مثال: 500.00"
                        {{ !$this->isEditable ? 'disabled' : '' }}
                    >
                    @error('deposit') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            
            @if(session()->has('message'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('message') }}</span>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-4">
                @if(!$isPinned)
                    <button wire:click="saveItinerary" wire:loading.attr="disabled" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-200 transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        حفظ كمسودة
                    </button>
                @elseif($this->isEditable)
                    <button wire:click="saveItinerary" wire:loading.attr="disabled" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-blue-200 transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        حفظ التعديلات (مسؤول)
                    </button>
                @endif

                @if($this->isEditable)
                    <button wire:click="pinItinerary" wire:loading.attr="disabled" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-emerald-200 transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" {{ $isPinned ? 'disabled' : '' }}>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        {{ $isPinned ? 'تم التثبيت' : 'تثبيت البرنامج' }}
                    </button>
                @endif
                <button wire:click="downloadPdf" wire:loading.attr="disabled" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-red-200 transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    تحميل PDF
                </button>
                <button wire:click="sendWhatsApp" wire:loading.attr="disabled" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-green-200 transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                    إرسال WhatsApp
                </button>
            </div>
        </div>
        @endif

        <!-- Loading Overlay -->
        <div wire:loading class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[9999] flex items-center justify-center transition-opacity duration-300">
            <div class="bg-white dark:bg-gray-800 px-8 py-6 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 flex flex-col items-center gap-4 max-w-xs w-full mx-4">
                <div class="relative flex items-center justify-center">
                    <div class="w-12 h-12 rounded-full border-4 border-blue-100 border-t-blue-600 animate-spin"></div>
                    <svg class="w-6 h-6 text-blue-600 absolute" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="text-gray-950 dark:text-white font-bold text-lg">جاري تنفيذ العملية</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">الرجاء الانتظار وعدم إغلاق الصفحة...</p>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="px-8 py-5 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
            @if($currentStep > 1)
                <button wire:click="previousStep" wire:loading.attr="disabled" class="text-gray-600 font-medium hover:text-gray-900 px-4 py-2 flex items-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    السابق
                </button>
            @else
                <div></div>
            @endif

            @if($currentStep < 3)
                <button wire:click="nextStep" wire:loading.attr="disabled" class="bg-blue-600 text-white px-8 py-2.5 rounded-xl font-bold hover:bg-blue-700 shadow-md shadow-blue-200 transition-all flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                    التالي
                    <svg class="w-5 h-5 mr-1 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            @else
                <div></div>
            @endif
        </div>
    </div>

    @if($itineraryId)
        @php
            $logs = \App\Models\ItineraryLog::where('itinerary_id', $itineraryId)->with('user')->orderBy('created_at', 'desc')->get();
        @endphp
        @if($logs->isNotEmpty())
            <div class="mt-8 bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100 p-8">
                <h3 class="text-xl font-bold text-gray-800 mb-6 pb-4 border-b">سجل تعديلات الطلب</h3>
                <div class="space-y-6">
                    @foreach($logs as $log)
                        <div class="border-r-4 border-blue-500 bg-gray-50 p-4 rounded-xl shadow-sm">
                            <div class="flex flex-wrap justify-between items-center mb-2">
                                <span class="font-bold text-blue-900 flex items-center gap-1">
                                    <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    بواسطة: {{ $log->user->name ?? 'غير معروف' }}
                                </span>
                                <span class="text-xs text-gray-500 font-medium">
                                    {{ $log->created_at->format('d-m-Y H:i:s') }}
                                </span>
                            </div>
                            <ul class="list-disc pr-5 text-sm text-gray-700 space-y-1">
                                @foreach($log->changes as $change)
                                    <li>{{ $change }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
