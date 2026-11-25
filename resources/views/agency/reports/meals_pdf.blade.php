<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>body{font-family:DejaVu Sans,sans-serif;font-size:11px;direction:rtl}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:4px}</style>
</head>
<body>
<h2>تقرير الوجبات</h2>
<p>الأشخاص: {{ $summary['people'] }} | إفطار: {{ $summary['breakfast'] }} | غداء: {{ $summary['lunch'] }} | عشاء: {{ $summary['dinner'] }}</p>
<table>
    <thead>
        <tr><th>#</th><th>الحجز</th><th>الراكب</th><th>الباقة</th><th>إفطار</th><th>غداء</th><th>عشاء</th></tr>
    </thead>
    <tbody>
    @foreach ($rows as $i => $s)
        @php $snap = $s->meal_snapshot ?? $s->meal_options ?? []; @endphp
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $s->booking_id }}</td>
            <td>{{ $s->passenger_name }}</td>
            <td>{{ $snap['package_name'] ?? '' }}</td>
            <td>{{ $s->with_breakfast ? 'نعم' : 'لا' }}</td>
            <td>{{ $s->with_lunch ? 'نعم' : 'لا' }}</td>
            <td>{{ $s->with_dinner ? 'نعم' : 'لا' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
