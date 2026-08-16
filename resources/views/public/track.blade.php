<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تتبع شحنة {{ $shipment->tracking_number }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Cairo',sans-serif;background:#f4f6f9}</style>
</head>
<body>
<div class="container py-5" style="max-width:500px">
    <div class="card p-4">
        <h5>تتبع الشحنة {{ $shipment->tracking_number }}</h5>
        <x-status-badge :status="$shipment->shipment_status" />
        @if (session('success'))<div class="alert alert-success mt-2">{{ session('success') }}</div>@endif
        <hr>
        <ul class="list-group list-group-flush">
            @foreach ($shipment->statusHistory as $h)
                <li class="list-group-item d-flex justify-content-between">
                    <x-status-badge :status="$h->status" />
                    <span class="text-muted small">{{ $h->created_at->format('Y-m-d H:i') }}</span>
                </li>
            @endforeach
        </ul>

        @if (in_array($shipment->shipment_status, ['out_for_delivery','no_answer','postponed']))
        <hr>
        <form method="POST" action="{{ route('track.reschedule', $shipment->tracking_number) }}">
            @csrf
            <label class="form-label small">طلب إعادة جدولة موعد التسليم</label>
            <div class="d-flex gap-2">
                <input type="date" name="new_date" class="form-control" required>
                <button class="btn btn-primary">إرسال</button>
            </div>
        </form>
        @endif
    </div>
</div>
</body>
</html>
