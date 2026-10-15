<a href="{{ route('admin.tenants.index') }}" class="btn btn-sm {{ request()->routeIs('admin.tenants.*') ? 'btn-primary' : 'btn-outline-secondary' }}">Businesses</a>
<a href="{{ route('admin.plans.index') }}" class="btn btn-sm {{ request()->routeIs('admin.plans.*') ? 'btn-primary' : 'btn-outline-secondary' }}">Plans</a>
<span class="navbar-text small mx-2">{{ auth('platform')->user()->name }}</span>
<form method="POST" action="{{ route('admin.logout') }}" class="m-0">
    @csrf
    <button type="submit" class="btn btn-outline-secondary btn-sm">Sign out</button>
</form>
