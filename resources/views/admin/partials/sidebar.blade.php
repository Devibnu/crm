<aside class="sidebar">
    @php
        $sidebarHref = fn (array $item): string => $item['href'] ?? (isset($item['url']) ? url($item['url']) : route($item['route']));
        $sidebarActivePatterns = function (array $item): array {
            if (isset($item['active'])) {
                return (array) $item['active'];
            }

            if (! isset($item['route'])) {
                return [];
            }

            $route = $item['route'];

            return str_ends_with($route, '.index')
                ? [$route, str($route)->beforeLast('.index')->append('.*')->toString()]
                : [$route];
        };
        $sidebarActive = fn (array $item): bool => request()->routeIs(...$sidebarActivePatterns($item));
        $sidebarDisabled = fn (array $item): bool => ($item['href'] ?? null) === '#'
            && ! isset($item['route'])
            && ! isset($item['url']);
    @endphp
    <a href="{{ route('admin.dashboard') }}" class="brand" aria-label="{{ $branding->display_app_name }} dashboard">
        <img src="{{ $branding->sidebar_logo_url }}" alt="" @class(['brand-mark', 'brand-mark-default' => ! $branding->sidebar_logo_path])>
        <span>{{ $branding->display_app_name }}</span>
    </a>

    <nav class="nav">
        <p class="nav-label">Dashboard</p>
        @foreach ($dashboardMenu as $item)
            <a href="{{ $sidebarHref($item) }}" @class(['nav-link parent compact', 'active' => $sidebarActive($item)])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Customer Profile 360</p>
        @foreach ($customersMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ $sidebarHref($item) }}" @class(['nav-link parent compact', 'active' => $sidebarActive($item)])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Sales Enablement</p>
        @foreach ($salesMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ $sidebarHref($item) }}" @class(['nav-link parent compact', 'active' => $sidebarActive($item)])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Project Management</p>
        @foreach ($projectMenu ?? [] as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ $sidebarHref($item) }}" @class(['nav-link parent compact', 'active' => $sidebarActive($item)]) @if($sidebarDisabled($item)) aria-disabled="true" @endif>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label">Marketing Automation</p>
        @foreach ($marketingMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ $sidebarHref($item) }}" @class(['nav-link parent compact', 'active' => $sidebarActive($item)])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        @php
            $sidebarUser = auth()->user();
            $visibleWhatsAppMarketingMenu = collect($whatsAppMarketingMenu)
                ->filter(fn ($item) => ! isset($item['permission'])
                    || $sidebarUser?->hasRole('super_admin')
                    || $sidebarUser?->can($item['permission']));
        @endphp
        @if ($visibleWhatsAppMarketingMenu->isNotEmpty())
            <p class="nav-label">WHATSAPP MARKETING</p>
            @foreach ($visibleWhatsAppMarketingMenu as $item)
                <a href="{{ $sidebarHref($item) }}" @class(['nav-link parent compact', 'active' => $sidebarActive($item)])>
                    <span class="nav-icon">
                        @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                    </span>
                    <span>{{ $item['title'] }}</span>
                </a>
            @endforeach
        @endif

        <p class="nav-label">SERVICE MANAGEMENT</p>
        @foreach ($serviceMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ $sidebarHref($item) }}" @class(['nav-link parent compact', 'active' => $sidebarActive($item)])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach

        @php
            $visibleSystemMenu = collect($systemMenu)
                ->filter(fn ($item) => ! isset($item['permission']) || $sidebarUser?->can($item['permission']));
        @endphp
        @if ($visibleSystemMenu->isNotEmpty())
            <p class="nav-label">System</p>
            @foreach ($visibleSystemMenu as $item)
                <a href="{{ $sidebarHref($item) }}" @class(['nav-link parent compact', 'active' => $sidebarActive($item)])>
                    <span class="nav-icon">
                        @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                    </span>
                    <span>{{ $item['title'] }}</span>
                </a>
            @endforeach
        @endif
    </nav>
</aside>
