<aside class="sidebar">
    <a href="{{ route('admin.dashboard') }}" class="brand" aria-label="Vuexy dashboard">
        <img src="{{ asset('assets/vuexy/logo.svg') }}" alt="" class="brand-mark">
        <span>Vuexy</span>
    </a>

    <nav class="nav">
        <details class="nav-group active-parent" open>
            <summary class="nav-link parent nav-toggle" aria-controls="dashboard-submenu">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v9h14v-9"/><path d="M9 19v-5h6v5"/></svg>
                </span>
                <span>Dashboards</span>
                <strong>5</strong>
                <span class="chevron">
                    <svg viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </summary>
            <div id="dashboard-submenu" class="nav-submenu">
                <a href="#" class="nav-link muted">Analytics</a>
                <a href="{{ route('admin.dashboard') }}" @class(['nav-link', 'active' => request()->routeIs('admin.dashboard'), 'muted' => ! request()->routeIs('admin.dashboard')])>CRM</a>
                <a href="#" class="nav-link muted">Ecommerce</a>
                <a href="#" class="nav-link muted">Academy</a>
                <a href="#" class="nav-link muted">Logistics</a>
            </div>
        </details>

        <p class="nav-label">Service Management</p>
        @foreach ($serviceMenu as $item)
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Sales Enablement</p>
        @foreach ($salesMenu as $item)
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Marketing Automation</p>
        @foreach ($marketingMenu as $item)
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Customer Profile 360</p>
        @foreach ($customersMenu as $item)
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
