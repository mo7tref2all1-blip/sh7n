<ul class="nav nav-pills flex-column gap-1">
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('merchant.dashboard') ? 'active' : '' }}" href="{{ route('merchant.dashboard') }}"><i class="bi bi-speedometer2 ms-1"></i> الرئيسية</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('merchant.shipments.*') ? 'active' : '' }}" href="{{ route('merchant.shipments.index') }}"><i class="bi bi-box-seam ms-1"></i> شحناتي</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('merchant.shipments.create') ? 'active' : '' }}" href="{{ route('merchant.shipments.create') }}"><i class="bi bi-plus-circle ms-1"></i> إنشاء شحنة</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('merchant.shipments.import.*') ? 'active' : '' }}" href="{{ route('merchant.shipments.import.show') }}"><i class="bi bi-file-earmark-excel ms-1"></i> رفع Excel</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('merchant.wallet.*') ? 'active' : '' }}" href="{{ route('merchant.wallet.index') }}"><i class="bi bi-wallet2 ms-1"></i> المحفظة</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('merchant.settlements.*') ? 'active' : '' }}" href="{{ route('merchant.settlements.index') }}"><i class="bi bi-receipt ms-1"></i> التسويات</a></li>
</ul>
