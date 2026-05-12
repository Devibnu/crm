<aside class="sidebar">
    <a href="{{ route('admin.dashboard') }}" class="brand" aria-label="Vuexy dashboard">
        <img src="{{ asset('assets/vuexy/logo.svg') }}" alt="" class="brand-mark">
        <span>Vuexy</span>
    </a>

    <nav class="nav">
        <p class="nav-label">Dashboard</p>
        @foreach ($dashboardMenu as $item)
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Service Management</p>
        @foreach ($serviceMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Sales Enablement</p>
        @foreach ($salesMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Marketing Automation</p>
        @foreach ($marketingMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Customer Profile 360</p>
        @foreach ($customersMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        @role('super_admin|admin')
            <p class="nav-label">System</p>
            @foreach ($systemMenu as $item)
                <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                    <span class="nav-icon">
                        @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                    </span>
                    <span>{{ $item['title'] }}</span>
                </a>
            @endforeach
        @endrole
    </nav>
</aside>
