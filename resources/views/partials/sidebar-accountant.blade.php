<ul class="nav nav-pills flex-column gap-1">
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('accountant.dashboard') ? 'active' : '' }}" href="{{ route('accountant.dashboard') }}"><i class="bi bi-speedometer2 ms-1"></i> الرئيسية</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('accountant.settlements.*') ? 'active' : '' }}" href="{{ route('accountant.settlements.index') }}"><i class="bi bi-receipt ms-1"></i> التسويات</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('accountant.handovers.*') ? 'active' : '' }}" href="{{ route('accountant.handovers.index') }}"><i class="bi bi-cash-coin ms-1"></i> توريدات نقدية</a></li>
</ul>
