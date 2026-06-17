<aside class="sidebar">
    <a href="{{ route('admin.dashboard') }}" class="brand" aria-label="{{ $branding->display_app_name }} dashboard">
        <img src="{{ $branding->sidebar_logo_url }}" alt="" @class(['brand-mark', 'brand-mark-default' => ! $branding->sidebar_logo_path])>
        <span>{{ $branding->display_app_name }}</span>
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

        <p class="nav-label">WHATSAPP MARKETING</p>
        @foreach ($whatsAppMarketingMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            @continue(isset($item['roles']) && auth()->check() && ! auth()->user()->hasAnyRole($item['roles']))
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">SERVICE MANAGEMENT</p>
        @foreach ($serviceMenu as $item)
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
                @php
                    $activePattern = $item['active'] ?? $item['route'];
                @endphp
                <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($activePattern)])>
                    <span class="nav-icon">
                        @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                    </span>
                    <span>{{ $item['title'] }}</span>
                </a>
            @endforeach
        @endrole
    </nav>
</aside>
