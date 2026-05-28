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
        <h1 class="title">قسيمة تأكيد الرحلة السياحية</h1>
        <div class="subtitle">السيد/ة: {{ $customerName }}</div>
    </div>

    <div class="section">
        <div class="section-title">بيانات الرحلة الأساسية</div>
        <table>
            <tr>
                <th width="25%">الوجهات</th>
                <td>{{ implode('، ', $destinations) }}</td>
                <th width="25%">عدد الأفراد</th>
                <td>{{ $adultsCount }} بالغين @if(count($childrenAges) > 0) و {{ count($childrenAges) }} أطفال (أعمارهم: {{ implode('، ', $childrenAges) }}) @endif</td>
            </tr>
            <tr>
                <th>تاريخ الوصول</th>
                <td>{{ $arrivingDate }} @if(!empty($arrivingTime)) (الساعة: {{ $arrivingTime }}) @endif</td>
                <th>تاريخ المغادرة</th>
                <td>{{ $leavingDate }} @if(!empty($leavingTime)) (الساعة: {{ $leavingTime }}) @endif</td>
            </tr>
            <tr>
                <th>المدة الإجمالية</th>
                <td colspan="3">{{ $totalDays }} أيام / {{ $totalNights }} ليالي</td>
            </tr>
        </table>
    </div>

    <!-- الأقسام السياحية للرحلة -->
    @foreach($segments as $segmentIndex => $seg)
        <div class="section">
            @php
                $segDests = \App\Models\Destination::whereIn('id', $seg['destinations'] ?? [])->pluck('name')->toArray();
                $segDestsStr = implode('، ', $segDests);
            @endphp
            <div class="section-title">القسم {{ $segmentIndex + 1 }}: {{ $segDestsStr }} ({{ $seg['nights'] }} ليالي)</div>

            <!-- أماكن الإقامة في هذا القسم -->
            @php
                $hasAccs = false;
                foreach($seg['accommodations'] ?? [] as $acc) {
                    if(!empty($acc['accommodation_id'])) {
                        $hasAccs = true;
                        break;
                    }
                }
            @endphp
            @if($hasAccs)
                <h4 style="color: #9d8155; margin-top: 0; margin-bottom: 8px;">الفنادق والإقامة للقسم:</h4>
                <table style="margin-bottom: 15px;">
                    <tr>
                        <th width="45%">اسم السكن</th>
                        <th width="30%">النوع</th>
                        <th width="25%">عدد الليالي</th>
                    </tr>
                    @foreach($seg['accommodations'] ?? [] as $acc)
                        @if(!empty($acc['accommodation_id']))
                            @php $accModel = $accommodations->find($acc['accommodation_id']); @endphp
                            <tr>
                                <td>
                                    {{ $accModel->name ?? 'غير محدد' }}
                                    @if(!empty($acc['note']))
                                        <br><span style="color:#666; font-size:12px;">ملاحظة: {{ $acc['note'] }}</span>
                                    @endif
                                    @if(!empty($accModel->video_url))
                                        <br><span style="font-size:12px;">رابط الفيديو: <a href="{{ $accModel->video_url }}" style="color:#2563eb; text-decoration:underline;" target="_blank">{{ $accModel->video_url }}</a></span>
                                    @endif
                                </td>
                                <td>{{ $accModel->type ?? '' }}</td>
                                <td>{{ $acc['nights'] }}</td>
                            </tr>
                        @endif
                    @endforeach
                </table>
            @endif

            <!-- البرنامج السياحي اليومي لهذا القسم -->
            @php
                $hasTours = false;
                if (!$includeRentalCar) {
                    foreach($seg['tours'] ?? [] as $tour) {
                        if (!empty($tour['tour_id'])) {
                            $hasTours = true;
                            break;
                        }
                    }
                }
            @endphp
            @if($hasTours)
                <h4 style="color: #9d8155; margin-top: 10px; margin-bottom: 8px;">البرنامج اليومي للقسم:</h4>
                <table>
                    <tr>
                        <th width="15%">اليوم</th>
                        <th width="25%">التاريخ</th>
                        <th width="60%">التفاصيل</th>
                    </tr>
                    @foreach($seg['tours'] ?? [] as $tourIndex => $tour)
                        @php
                            $startDay = 1;
                            for ($prevIdx = 0; $prevIdx < $segmentIndex; $prevIdx++) {
                                $startDay += $segments[$prevIdx]['nights'] ?? 0;
                            }
                            $absoluteDayNumber = $startDay + $tourIndex;
                        @endphp
                        <tr>
                            <td style="font-weight: bold;">اليوم {{ $absoluteDayNumber }}</td>
                            <td>{{ $tour['date'] ?? '' }}</td>
                            <td>
                                @if(!empty($tour['tour_id']))
                                    @php $tourModel = $tours->find($tour['tour_id']); @endphp
                                    <strong>{{ $tourModel->name ?? '' }} ({{ $tourModel->type ?? '' }})</strong>
                                    @if(!empty($tourModel->short_description))
                                        <br><span style="color:#666; font-size:12px;">{{ $tourModel->short_description }}</span>
                                    @endif
                                    @if(!empty($tourModel->external_link))
                                        <br><a href="{{ $tourModel->external_link }}" style="color:#cf9c56; font-size:12px; text-decoration:underline;">مزيد من التفاصيل</a>
                                    @endif
                                @else
                                    <span style="color: #999;">يوم حر / لم يتم تحديد جولة</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    @endforeach

    <!-- استئجار سيارة -->
    @if($includeRentalCar && !empty($selectedCarId))
    <div class="section">
        <div class="section-title">استئجار سيارة</div>
        <table>
            <tr>
                <th width="30%">نوع السيارة</th>
                <td>{{ $cars->find($selectedCarId)->car_type ?? '' }}</td>
                <th width="30%">مدة الاستئجار</th>
                <td>{{ $totalDays }} أيام</td>
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

    <!-- صور مرفقة -->
    @php
        $hasImages = false;
        $accImages = [];
        foreach($segments as $seg) {
            foreach($seg['accommodations'] ?? [] as $acc) {
                if(!empty($acc['accommodation_id'])) {
                    $accModel = $accommodations->find($acc['accommodation_id']);
                    if ($accModel && !empty($accModel->images)) {
                        $accImages[$accModel->name] = $accModel->images;
                        $hasImages = true;
                    }
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

    <!-- إظهار الfooter المعرف بالsettings بنهاية اخر صفحة في ملف الvoucher بحيث تكون هناك ثابتة -->
    @if(!empty($additionalDetails))
    <htmlpagefooter name="voucherFooter">
        <div style="border-top: 1px solid #cf9c56; padding-top: 10px; font-size: 11px; text-align: center; color: #6b7280;">
            {!! $additionalDetails !!}
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="voucherFooter" value="1" />
    @endif

</body>
</html>
