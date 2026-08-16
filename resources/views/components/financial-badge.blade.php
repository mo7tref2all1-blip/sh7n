@php [$label, $color] = \App\Support\StatusLabels::financial($status); @endphp
<span class="badge bg-{{ $color }} badge-status">{{ $label }}</span>
