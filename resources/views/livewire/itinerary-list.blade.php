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
                    <tbody>
                        @foreach($itineraries as $itinerary)
                        <tr class="bg-white border-b hover:bg-gray-50">
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
                                <button wire:click="loadItinerary({{ $itinerary->id }})" class="text-blue-600 hover:text-blue-800 font-medium">
                                    استعراض وتعديل
                                </button>
                                @if($this->isAdminOrSuperAdmin())
                                    <button 
                                        wire:click="deleteItinerary({{ $itinerary->id }})" 
                                        wire:confirm="هل أنت متأكد من رغبتك في حذف هذا البرنامج السياحي؟"
                                        class="text-red-600 hover:text-red-800 font-medium mr-4"
                                    >
                                        حذف
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
