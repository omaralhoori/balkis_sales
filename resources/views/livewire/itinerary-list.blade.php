<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">طلباتي المحفوظة</h2>
        <a href="{{ route('home') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition-colors">
            + إنشاء رحلة جديدة
        </a>
    </div>

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
                            <th class="px-6 py-4 border-b">الوصول</th>
                            <th class="px-6 py-4 border-b">المدة</th>
                            <th class="px-6 py-4 border-b">تاريخ الحفظ</th>
                            <th class="px-6 py-4 border-b">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($itineraries as $itinerary)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-bold text-gray-900">
                                {{ $itinerary->customer_name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $itinerary->arriving_date->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $itinerary->total_days }} أيام / {{ $itinerary->total_nights }} ليالي
                            </td>
                            <td class="px-6 py-4" dir="ltr" style="text-align: right">
                                {{ $itinerary->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="loadItinerary({{ $itinerary->id }})" class="text-blue-600 hover:text-blue-800 font-medium">
                                    استعراض وتعديل
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
