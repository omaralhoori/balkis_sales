<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>قسيمة رحلة سياحية</title>
    <style>
        body {
            font-family: 'cairo', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            direction: rtl;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #cf9c56;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .title {
            color: #9d8155;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .subtitle {
            color: #6b7280;
            font-size: 16px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            background-color: #49576d;
            color: #fff;
            padding: 8px 15px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 10px;
            text-align: right;
        }
        th {
            background-color: #f3f4f6;
            color: #4b5563;
            font-weight: bold;
        }
        .total-box {
            background-color: #ecfdf5;
            border: 1px solid #10b981;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
            margin-top: 30px;
        }
        .total-title {
            font-size: 18px;
            color: #065f46;
            margin-bottom: 5px;
        }
        .total-amount {
            font-size: 28px;
            font-weight: bold;
            color: #047857;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="title">تفاصيل البرنامج السياحي</h1>
        <div class="subtitle">السيد/ة: {{ $customerName }}</div>
    </div>

    <div class="section">
        <div class="section-title">بيانات الرحلة الأساسية</div>
        <table>
            <tr>
                <th width="25%">عدد الأفراد</th>
                <td>{{ $adultsCount }} بالغين @if(count($childrenAges) > 0) و {{ count($childrenAges) }} أطفال (أعمارهم: {{ implode('، ', $childrenAges) }}) @endif</td>
                <th width="25%">المدة الإجمالية</th>
                <td>{{ $totalDays }} أيام / {{ $totalNights }} ليالي</td>
            </tr>
            <tr>
                <th>تاريخ الوصول</th>
                <td>{{ $arrivingDate }} @if(!empty($arrivingTime)) (الساعة: {{ $arrivingTime }}) @endif</td>
                <th>تاريخ المغادرة</th>
                <td>{{ $leavingDate }} @if(!empty($leavingTime)) (الساعة: {{ $leavingTime }}) @endif</td>
            </tr>
            @if(!empty($customerWhatsapp))
            <tr>
                <th>رقم الواتساب</th>
                <td colspan="3" dir="ltr" style="text-align: right">+{{ $customerWhatsapp }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- تفاصيل البرنامج اليومي -->
    <div class="section">
        <div class="section-title">تفاصيل البرنامج اليومي</div>
        <table>
            <thead>
                <tr>
                    <th width="15%">اليوم والتاريخ</th>
                    <th width="42%">السكن والإقامة</th>
                    <th width="43%">البرنامج اليومي والجولات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailySlots as $index => $slot)
                    <tr>
                        <td style="font-weight: bold; vertical-align: top;">
                            اليوم {{ $index + 1 }}
                            <br><span style="color: #6b7280; font-size: 12px;">{{ $slot['date'] }}</span>
                        </td>
                        <td style="vertical-align: top;">
                            @if($index < $totalNights && !empty($slot['accommodation']['accommodation_id']))
                                @php $accModel = $accommodations->find($slot['accommodation']['accommodation_id']); @endphp
                                @if($accModel)
                                    <strong>{{ $accModel->name }}</strong>
                                    @if(!empty($slot['accommodation']['room_type']))
                                        <br><span style="color: #4b5563; font-size: 12px; font-weight: bold;">
                                            @if($slot['accommodation']['room_type'] === 'أخرى' || $slot['accommodation']['room_type'] === 'عدد الأشخاص')
                                                {{ $slot['accommodation']['custom_room_type'] ?? '' }}
                                            @else
                                                {{ $slot['accommodation']['room_type'] }}
                                            @endif
                                        </span>
                                    @endif
                                    @if(!empty($slot['accommodation']['note']))
                                        <br><span style="color:#666; font-size:12px;">ملاحظة: {{ $slot['accommodation']['note'] }}</span>
                                    @endif
                                    @if(!empty($accModel->video_url))
                                        <br><span style="font-size:12px;">رابط الفيديو: <a href="{{ $accModel->video_url }}" style="color:#2563eb; text-decoration:underline;" target="_blank">{{ $accModel->video_url }}</a></span>
                                    @endif
                                @else
                                    <span style="color: #999;">غير محدد</span>
                                @endif
                            @else
                                <span style="color: #999;">لا يوجد سكن (يوم المغادرة)</span>
                            @endif
                        </td>
                        <td style="vertical-align: top;">
                            @php
                                $hasNoCarOnThisDay = !$includeRentalCar || ($index == 0 && $excludeCarFirstDay) || ($index == count($dailySlots) - 1 && $excludeCarLastDay);
                            @endphp
                            @if($hasNoCarOnThisDay)
                                @if(!empty($slot['tour']['tour_id']))
                                    @php $tourModel = $tours->find($slot['tour']['tour_id']); @endphp
                                    @if($tourModel)
                                        <strong>{{ $tourModel->name }}</strong> ({{ $tourModel->type ?? '' }})
                                        @if(!empty($tourModel->short_description))
                                            <br><span style="color:#666; font-size:12px;">{{ $tourModel->short_description }}</span>
                                        @endif
                                        @if(!empty($tourModel->external_link))
                                            <br><a href="{{ $tourModel->external_link }}" style="color:#cf9c56; font-size:12px; text-decoration:underline;">مزيد من التفاصيل</a>
                                        @endif
                                    @else
                                        <span style="color: #999;">يوم حر / لم يتم تحديد جولة</span>
                                    @endif
                                @else
                                    <span style="color: #999;">يوم حر / لم يتم تحديد جولة</span>
                                @endif
                            @else
                                <span style="color: #999;">مشمول ضمن سيارة بدون سائق</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- استئجار سيارة -->
    @if($includeRentalCar && !empty($selectedCarId))
    <div class="section">
        <div class="section-title">استئجار سيارة</div>
        <table>
            <tr>
                <th width="30%">نوع السيارة</th>
                <td>{{ $cars->find($selectedCarId)->car_type ?? '' }}</td>
                <th width="30%">مدة الاستئجار</th>
                <td>
                    @php
                        $carDays = $totalDays - ($excludeCarFirstDay ? 1 : 0) - ($excludeCarLastDay ? 1 : 0);
                        $exclusions = [];
                        if ($excludeCarFirstDay) $exclusions[] = 'اليوم الأول';
                        if ($excludeCarLastDay) $exclusions[] = 'اليوم الأخير';
                        $exclusionsStr = !empty($exclusions) ? ' (باستثناء ' . implode(' و ', $exclusions) . ')' : '';
                    @endphp
                    {{ max(0, $carDays) }} أيام{{ $exclusionsStr }}
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- ملاحظات الفاتورة -->
    @if(!empty($voucherNotes))
    <div class="section">
        <div class="section-title">ملاحظات إضافية</div>
        <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; padding: 15px; border-radius: 6px; white-space: pre-line; font-size: 13px;">{{ $voucherNotes }}</div>
    </div>
    @endif

    <!-- معلومات الدفع والتسعير -->
    <pagebreak />
    <div style="page-break-inside: avoid; break-inside: avoid;">
        @if(!empty($deposit) && $deposit > 0)
            <table style="width: 100%; margin-top: 30px; border: 1px solid #10b981; background-color: #ecfdf5; border-collapse: collapse;">
                <tr>
                    <td style="padding: 15px; text-align: center; border: none; width: 33.3%;">
                        <div style="font-size: 15px; color: #065f46; margin-bottom: 5px; font-weight: bold;">سعر المبيع الإجمالي</div>
                        <div style="font-size: 22px; font-weight: bold; color: #047857;">${{ number_format($finalSellingPrice, 2) }}</div>
                    </td>
                    <td style="padding: 15px; text-align: center; border-right: 1px solid #10b981; border-top: none; border-bottom: none; border-left: none; width: 33.3%;">
                        <div style="font-size: 15px; color: #9a3412; margin-bottom: 5px; font-weight: bold;">العربون المدفوع</div>
                        <div style="font-size: 22px; font-weight: bold; color: #c2410c;">${{ number_format($deposit, 2) }}</div>
                    </td>
                    <td style="padding: 15px; text-align: center; border-right: 1px solid #10b981; border-top: none; border-bottom: none; border-left: none; width: 33.3%;">
                        <div style="font-size: 15px; color: #2563eb; margin-bottom: 5px; font-weight: bold;">المبلغ المتبقي</div>
                        <div style="font-size: 22px; font-weight: bold; color: #2563eb;">${{ number_format($remaining, 2) }}</div>
                    </td>
                </tr>
            </table>
        @else
            <div class="total-box">
                <div class="total-title">سعر المبيع الإجمالي</div>
                <div class="total-amount">${{ number_format($finalSellingPrice, 2) }}</div>
            </div>
        @endif
    </div>

    <!-- صور مرفقة -->
    @php
        $hasImages = false;
        $accImages = [];
        foreach($dailySlots as $index => $slot) {
            if($index < $totalNights && !empty($slot['accommodation']['accommodation_id'])) {
                $accModel = $accommodations->find($slot['accommodation']['accommodation_id']);
                if ($accModel && !empty($accModel->images)) {
                    $accImages[$accModel->name] = $accModel->images;
                    $hasImages = true;
                }
            }
        }
        $carImages = [];
        if($includeRentalCar && !empty($selectedCarId)) {
            $carModel = $cars->find($selectedCarId);
            if ($carModel && !empty($carModel->images)) {
                $carImages[$carModel->car_type] = $carModel->images;
                $hasImages = true;
            }
        }
    @endphp

    @if($hasImages)
    <div class="section mt-5" style="margin-top: 25px">
        <div class="section-title">صور مرفقة</div>
        
        @foreach($accImages as $name => $images)
            <h3 style="color: #cf9c56; margin-bottom: 10px;">{{ $name }}</h3>
            <div style="text-align: center; margin-bottom: 20px;">
                @foreach($images as $img)
                    @php
                        $imagePath = storage_path('app/public/' . $img);
                        if (!file_exists($imagePath)) {
                            $imagePath = storage_path('app/private/' . $img);
                        }
                        if (!file_exists($imagePath)) {
                            $imagePath = storage_path('app/' . $img);
                        }
                    @endphp
                    @if(file_exists($imagePath))
                        <img src="{{ $imagePath }}" style="max-width: 250px; max-height: 200px; margin: 5px; border-radius: 5px; border: 1px solid #ccc; display: inline-block;">
                    @endif
                @endforeach
            </div>
        @endforeach

        @foreach($carImages as $name => $images)
            <h3 style="color: #cf9c56; margin-bottom: 10px;">{{ $name }}</h3>
            <div style="text-align: center; margin-bottom: 20px;">
                @foreach($images as $img)
                    @php
                        $imagePath = storage_path('app/public/' . $img);
                        if (!file_exists($imagePath)) {
                            $imagePath = storage_path('app/private/' . $img);
                        }
                        if (!file_exists($imagePath)) {
                            $imagePath = storage_path('app/' . $img);
                        }
                    @endphp
                    @if(file_exists($imagePath))
                        <img src="{{ $imagePath }}" style="max-width: 250px; max-height: 200px; margin: 5px; border-radius: 5px; border: 1px solid #ccc; display: inline-block;">
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
    @endif

    <!-- إظهار الfooter المعرف بالsettings بنهاية ملف الvoucher مباشرة -->
    @if(!empty($additionalDetails))
    <div style="border-top: 1px solid #cf9c56; padding-top: 10px; margin-top: 30px; font-size: 11px; text-align: center; color: #6b7280; page-break-inside: avoid;">
        {!! $additionalDetails !!}
    </div>
    @endif

</body>
</html>
