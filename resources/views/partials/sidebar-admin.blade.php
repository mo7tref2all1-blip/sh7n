<ul class="nav nav-pills flex-column gap-1">
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 ms-1"></i> الرئيسية</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.shipments.*') ? 'active' : '' }}" href="{{ route('admin.shipments.index') }}"><i class="bi bi-box-seam ms-1"></i> الشحنات</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.reconciliation') ? 'active' : '' }}" href="{{ route('admin.reconciliation') }}"><i class="bi bi-clipboard-check ms-1"></i> المطابقة المالية</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}" href="{{ route('admin.branches.index') }}"><i class="bi bi-building ms-1"></i> الفروع</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.merchants.*') ? 'active' : '' }}" href="{{ route('admin.merchants.index') }}"><i class="bi bi-shop ms-1"></i> التجار</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.agents.*') ? 'active' : '' }}" href="{{ route('admin.agents.index') }}"><i class="bi bi-people ms-1"></i> الوكلاء</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}" href="{{ route('admin.pricing.index') }}"><i class="bi bi-tags ms-1"></i> التسعير</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.activity.*') ? 'active' : '' }}" href="{{ route('admin.activity.index') }}"><i class="bi bi-activity ms-1"></i> النشاط والتدقيق</a></li>
</ul>
