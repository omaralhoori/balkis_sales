<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
    @if(session()->has('message'))
        <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            {{ $this->isAdminOrSuperAdmin() ? 'الطلبات المحفوظة (المسؤول)' : 'طلباتي المحفوظة' }}
        </h2>
        <a href="{{ route('home') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition-colors">
            + إنشاء رحلة جديدة
        </a>
    </div>

    @if($this->isAdminOrSuperAdmin())
        <div class="mb-6 bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
            <span class="text-sm font-bold text-gray-700">تصفية حسب الموظف:</span>
            <select wire:model.live="selectedEmployeeId" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-1.5 px-3">
                <option value="">كل الموظفين</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->email }})</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
        @if($itineraries->isEmpty())
            <div class="p-12 text-center text-gray-500">
                لا يوجد رحلات محفوظة مسبقاً.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 border-b">اسم العميل</th>
                            @if($this->isAdminOrSuperAdmin())
                                <th class="px-6 py-4 border-b">الموظف</th>
                            @endif
                            <th class="px-6 py-4 border-b">الوصول</th>
                            <th class="px-6 py-4 border-b">المدة</th>
                            <th class="px-6 py-4 border-b">تاريخ الحفظ</th>
                            <th class="px-6 py-4 border-b text-center">إجراءات</th>
                        </tr>
                    </thead>
                    @foreach($itineraries as $itinerary)
                    <tbody x-data="{ openLogs: false }" class="divide-y divide-gray-100 border-b last:border-b-0">
                        <tr class="bg-white hover:bg-gray-50">
                            <td class="px-6 py-4 font-bold text-gray-900 flex items-center gap-2">
                                <span>{{ $itinerary->customer_name }}</span>
                                @if(!empty($itinerary->customer_whatsapp))
                                    <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $itinerary->customer_whatsapp) }}" target="_blank" class="text-green-600 hover:text-green-800 ml-2">
                                        (واتساب)
                                    </a>
                                @endif
                                @if($itinerary->is_pinned)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">مثبت</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">مسودة</span>
                                @endif
                            </td>
                            @if($this->isAdminOrSuperAdmin())
                                <td class="px-6 py-4 text-gray-700">
                                    {{ $itinerary->user->name ?? 'غير معروف' }}
                                </td>
                            @endif
                            <td class="px-6 py-4">
                                {{ $itinerary->arriving_date->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $itinerary->total_days }} أيام / {{ $itinerary->total_nights }} ليالي
                            </td>
                            <td class="px-6 py-4" dir="ltr" style="text-align: right">
                                {{ $itinerary->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <button wire:click="loadItinerary({{ $itinerary->id }})" class="text-blue-600 hover:text-blue-800 font-medium">
                                        استعراض وتعديل
                                    </button>
                                    @if($itinerary->logs->isNotEmpty())
                                        <button @click="openLogs = !openLogs" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                                            <span x-text="openLogs ? 'إخفاء السجل' : 'سجل التعديلات'"></span>
                                            <span class="bg-indigo-100 text-indigo-800 text-xs px-1.5 py-0.5 rounded-full font-bold">
                                                {{ $itinerary->logs->count() }}
                                            </span>
                                        </button>
                                    @endif
                                    @if($this->isAdminOrSuperAdmin())
                                        <button 
                                            wire:click="deleteItinerary({{ $itinerary->id }})" 
                                            wire:confirm="هل أنت متأكد من رغبتك في حذف هذا البرنامج السياحي؟"
                                            class="text-red-600 hover:text-red-800 font-medium"
                                        >
                                            حذف
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($itinerary->logs->isNotEmpty())
                            <tr x-show="openLogs" x-cloak class="bg-indigo-50/20">
                                <td colspan="{{ $this->isAdminOrSuperAdmin() ? 6 : 5 }}" class="px-6 py-4">
                                    <div class="border-r-4 border-indigo-500 bg-white p-4 rounded-xl shadow-sm space-y-4">
                                        <h4 class="font-bold text-indigo-900 text-sm mb-2">تاريخ تعديل الطلب:</h4>
                                        <div class="divide-y divide-gray-100">
                                            @foreach($itinerary->logs as $log)
                                                <div class="py-3 first:pt-0 last:pb-0">
                                                    <div class="flex justify-between items-center text-xs text-gray-500 mb-2">
                                                        <span class="font-bold text-indigo-900 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                            بواسطة: {{ $log->user->name ?? 'غير معروف' }}
                                                        </span>
                                                        <span class="font-medium text-gray-500">{{ $log->created_at->format('d-m-Y H:i:s') }}</span>
                                                    </div>
                                                    <ul class="list-disc pr-5 text-xs text-gray-700 space-y-1">
                                                        @foreach($log->changes as $change)
                                                            <li>{{ $change }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
</div>
