<aside class="sidebar">
    @php($visibleDashboardMenu = \App\Support\DashboardAccess::visibleMenuItems($dashboardMenu ?? [], auth()->user()))
    @php($shellTranslations = [
        'Dashboard' => 'Dasbor',
        'Service Management' => 'Manajemen Layanan',
        'Sales Enablement' => 'Pemberdayaan Penjualan',
        'Marketing Automation' => 'Otomasi Pemasaran',
        'Customer Profile 360' => 'Profil Pelanggan 360',
        'System' => 'Sistem',
        'CRM Overview' => 'Ringkasan CRM',
        'Omnichannel Inbox' => 'Kotak Masuk Omnichannel',
        'Ticket Management' => 'Manajemen Tiket',
        'SLA Management' => 'Manajemen SLA',
        'Case Resolution' => 'Resolusi Kasus',
        'Customer Satisfaction' => 'Kepuasan Pelanggan',
        'Knowledge Base' => 'Basis Pengetahuan',
        'Lead Management' => 'Manajemen Prospek',
        'Opportunity Management' => 'Manajemen Peluang',
        'Pipeline & Forecasting' => 'Pipeline & Peramalan',
        'Sales Activity Tracking' => 'Pelacakan Aktivitas Penjualan',
        'Quotation & Deal' => 'Penawaran & Deal',
        'Win/Lost Analysis' => 'Analisis Menang/Kalah',
        'Campaign Management' => 'Manajemen Kampanye',
        'Audience Segmentation' => 'Segmentasi Audiens',
        'Campaign Execution' => 'Eksekusi Kampanye',
        'Landing Page & Form' => 'Landing Page & Formulir',
        'Social Media Engagement' => 'Keterlibatan Media Sosial',
        'Automation & Nurturing' => 'Otomasi & Nurturing',
        'WhatsApp Broadcast' => 'Siaran WhatsApp',
        'WhatsApp Reply Inbox' => 'Kotak Masuk Balasan WhatsApp',
        'Lead Scoring & Routing' => 'Skor & Routing Prospek',
        'Customer List' => 'Daftar Pelanggan',
        'Customer Profile' => 'Profil Pelanggan',
        'Interaction History' => 'Riwayat Interaksi',
        'Preferences' => 'Preferensi',
        'Transactions' => 'Transaksi',
        'Behavior' => 'Perilaku',
        'Users' => 'Pengguna',
        'Roles & Permissions' => 'Role & Izin',
        'Dynamic Menus' => 'Menu Dinamis',
    ])

    <a href="{{ route('admin.dashboard') }}" class="brand" aria-label="Vuexy dashboard">
        <img src="{{ asset('assets/vuexy/logo.svg') }}" alt="" class="brand-mark">
        <span>Vuexy</span>
    </a>

    <nav class="nav">
        @if ($visibleDashboardMenu !== [])
            <p class="nav-label" data-lang-en="Dashboard" data-lang-id="Dasbor">Dashboard</p>
            @foreach ($visibleDashboardMenu as $item)
                <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                    <span class="nav-icon">
                        @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                    </span>
                    <span data-lang-en="{{ $item['title'] }}" data-lang-id="{{ $shellTranslations[$item['title']] ?? $item['title'] }}">{{ $item['title'] }}</span>
                </a>
            @endforeach
        @endif

        <p class="nav-label" data-lang-en="Service Management" data-lang-id="Manajemen Layanan">Service Management</p>
        @foreach ($serviceMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span data-lang-en="{{ $item['title'] }}" data-lang-id="{{ $shellTranslations[$item['title']] ?? $item['title'] }}">{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label" data-lang-en="Sales Enablement" data-lang-id="Pemberdayaan Penjualan">Sales Enablement</p>
        @foreach ($salesMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span data-lang-en="{{ $item['title'] }}" data-lang-id="{{ $shellTranslations[$item['title']] ?? $item['title'] }}">{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label" data-lang-en="Marketing Automation" data-lang-id="Otomasi Pemasaran">Marketing Automation</p>
        @foreach ($marketingMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span data-lang-en="{{ $item['title'] }}" data-lang-id="{{ $shellTranslations[$item['title']] ?? $item['title'] }}">{{ $item['title'] }}</span>
            </a>
        @endforeach

        <p class="nav-label" data-lang-en="Customer Profile 360" data-lang-id="Profil Pelanggan 360">Customer Profile 360</p>
        @foreach ($customersMenu as $item)
            @continue(isset($item['permission']) && auth()->check() && ! auth()->user()->can($item['permission']))
            <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                <span class="nav-icon">
                    @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                </span>
                <span data-lang-en="{{ $item['title'] }}" data-lang-id="{{ $shellTranslations[$item['title']] ?? $item['title'] }}">{{ $item['title'] }}</span>
            </a>
        @endforeach

        @role('super_admin|admin')
            <p class="nav-label" data-lang-en="System" data-lang-id="Sistem">System</p>
            @foreach ($systemMenu as $item)
                <a href="{{ route($item['route']) }}" @class(['nav-link parent compact', 'active' => request()->routeIs($item['route'])])>
                    <span class="nav-icon">
                        @include('admin.partials.sidebar-icon', ['icon' => $item['icon']])
                    </span>
                    <span data-lang-en="{{ $item['title'] }}" data-lang-id="{{ $shellTranslations[$item['title']] ?? $item['title'] }}">{{ $item['title'] }}</span>
                </a>
            @endforeach
        @endrole
    </nav>
</aside>
