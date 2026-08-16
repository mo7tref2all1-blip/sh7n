@php [$label, $color] = \App\Support\StatusLabels::shipment($status); @endphp
<span class="badge bg-{{ $color }} badge-status">{{ $label }}</span>
