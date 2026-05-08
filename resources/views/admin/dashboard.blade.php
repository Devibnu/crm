<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Krakatau CRM</title>
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
</head>
<body>
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
                        @switch($item['icon'])
                            @case('inbox')
                                <svg viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/><path d="M4 13h5l2 3h2l2-3h5"/></svg>
                                @break
                            @case('ticket')
                                <svg viewBox="0 0 24 24"><path d="M4 7h16v4a2 2 0 0 0 0 4v4H4v-4a2 2 0 0 0 0-4z"/><path d="M9 7v12"/></svg>
                                @break
                            @case('timer')
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="13" r="8"/><path d="M12 13V8"/><path d="M12 13l4 2"/><path d="M9 2h6"/></svg>
                                @break
                            @case('case')
                                <svg viewBox="0 0 24 24"><path d="M4 7h16v12H4z"/><path d="M9 7V5h6v2"/><path d="m8 14 2 2 5-5"/></svg>
                                @break
                            @case('star')
                                <svg viewBox="0 0 24 24"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"/></svg>
                                @break
                            @default
                                <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M8 9h8"/><path d="M8 13h8"/></svg>
                        @endswitch
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

    <main class="app-shell">
        <header class="topbar">
            <div class="search">
                <span class="search-icon">
                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                </span>
                <input type="search" placeholder="Search">
                <kbd>Cmd K</kbd>
            </div>
            <div class="top-actions">
                <button type="button" aria-label="Language"><svg viewBox="0 0 24 24"><path d="M4 5h9"/><path d="M9 3v2"/><path d="M6 5c.7 3.7 3 6.3 7 8"/><path d="M12 5c-.7 3.4-3 6.2-7 8"/><path d="M14 19l3-7 3 7"/><path d="M15 17h4"/></svg></button>
                <button type="button" aria-label="Theme"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg></button>
                <button type="button" aria-label="Apps"><svg viewBox="0 0 24 24"><path d="M4 4h6v6H4z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6H4z"/><path d="M14 14h6v6h-6z"/></svg></button>
                <button type="button" class="notification" aria-label="Notifications"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg></button>
                <img src="{{ asset('assets/avatars/avatar-1.png') }}" alt="Admin profile">
            </div>
        </header>

        <section class="dashboard-grid">
            @foreach ($stats as $stat)
                <article class="card stat-card">
                    <div>
                        <h2>{{ $stat['title'] }}</h2>
                        <p>{{ $stat['subtitle'] }}</p>
                    </div>

                    @if (isset($stat['bars']))
                        <div class="mini-bars">
                            @foreach ($stat['bars'] as $bar)
                                <span style="height: {{ $bar }}%"></span>
                            @endforeach
                        </div>
                    @elseif (isset($stat['line']))
                        <svg viewBox="0 0 260 100" class="mini-line" role="img" aria-label="Sales trend">
                            <defs>
                                <linearGradient id="lineFill" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#28c76f" stop-opacity=".25" />
                                    <stop offset="100%" stop-color="#28c76f" stop-opacity="0" />
                                </linearGradient>
                            </defs>
                            <path d="{{ $stat['line'] }} L260 100 L0 100 Z" fill="url(#lineFill)" />
                            <path d="{{ $stat['line'] }}" fill="none" stroke="#28c76f" stroke-width="4" stroke-linecap="round" />
                        </svg>
                    @else
                        <div class="metric-icon {{ $stat['tone'] }}">
                            @if ($stat['icon'] === 'cash')
                                <svg viewBox="0 0 24 24"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                            @else
                                <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/></svg>
                            @endif
                        </div>
                    @endif

                    <div class="stat-footer">
                        <strong>{{ $stat['value'] }}</strong>
                        <span class="pill {{ $stat['tone'] }}">{{ $stat['change'] }}</span>
                    </div>
                </article>
            @endforeach

            <article class="card revenue-card">
                <div>
                    <h2>Revenue Growth</h2>
                    <p>Weekly Report</p>
                </div>
                <div class="revenue-content">
                    <div>
                        <strong>$4,673</strong>
                        <span class="pill success">+15.2%</span>
                    </div>
                    <div class="weekly-bars">
                        @foreach ([32, 48, 62, 78, 92, 78, 62] as $index => $bar)
                            <span class="{{ $index === 4 ? 'active' : '' }}" style="height: {{ $bar }}%"></span>
                        @endforeach
                    </div>
                </div>
                <div class="week-labels"><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span></div>
            </article>

            <article class="card earnings-card">
                <div class="card-head">
                    <div>
                        <h2>Earning Reports</h2>
                        <p>Yearly Earnings Overview</p>
                    </div>
                    <button type="button">...</button>
                </div>
                <div class="report-tabs">
                    @foreach ([['Orders', 'O', true], ['Sales', 'S', false], ['Profit', '$', false], ['Income', 'I', false], ['', '+', false]] as [$label, $icon, $active])
                        <button type="button" @class(['selected' => $active])>
                            <span>
                                @switch($label)
                                    @case('Orders')
                                        <svg viewBox="0 0 24 24"><path d="M4 5h2l2 10h10l2-7H7"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                                        @break
                                    @case('Sales')
                                        <svg viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-8"/></svg>
                                        @break
                                    @case('Profit')
                                        <svg viewBox="0 0 24 24"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                                        @break
                                    @case('Income')
                                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/></svg>
                                        @break
                                    @default
                                        {{ $icon }}
                                @endswitch
                            </span>
                            @if ($label)
                                {{ $label }}
                            @endif
                        </button>
                    @endforeach
                </div>
                <div class="bar-chart">
                    <div class="axis">
                        @foreach (['50k', '40k', '30k', '20k', '10k', '0k'] as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>
                    <div class="bar-stage">
                        @foreach ($earningBars as $bar)
                            <div class="bar-item">
                                <strong>{{ $bar['value'] }}k</strong>
                                <span @class(['active' => $bar['active'] ?? false]) style="height: {{ $bar['value'] * 2.2 }}%"></span>
                                <small>{{ $bar['month'] }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>

            <article class="card sales-card">
                <div class="card-head">
                    <div>
                        <h2>Sales</h2>
                        <p>Last 6 Months</p>
                    </div>
                    <button type="button">...</button>
                </div>
                <div class="radar">
                    <svg viewBox="0 0 320 280" role="img" aria-label="Sales and visits radar chart">
                        <polygon points="160,20 265,80 265,200 160,260 55,200 55,80" class="grid-line" />
                        <polygon points="160,60 230,100 230,180 160,220 90,180 90,100" class="grid-line" />
                        <polygon points="160,20 160,260 55,80 265,200 265,80 55,200" class="spokes" />
                        <polygon points="160,56 235,106 216,180 160,218 96,178 96,104" class="sales-poly" />
                        <polygon points="160,84 258,82 214,180 154,198 112,160 116,98" class="visit-poly" />
                        <text x="160" y="10">Jan</text><text x="278" y="82">Feb</text><text x="282" y="204">Mar</text>
                        <text x="160" y="276">Apr</text><text x="28" y="204">May</text><text x="28" y="82">Jun</text>
                    </svg>
                </div>
                <div class="legend"><span class="dot primary"></span> Sales <span class="dot info"></span> Visits</div>
            </article>
        </section>

        <button class="settings" type="button" aria-label="Settings"><svg viewBox="0 0 24 24"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 8.6 19a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 5 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.1A1.7 1.7 0 0 0 15.4 5a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 .6 1 1.7 1.7 0 0 0 1.1.4h.1a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.7.6Z"/></svg></button>
        <a class="buy-now" href="https://pixinvent.com/vuexy-vuetify-vuejs-admin-template/" target="_blank" rel="noreferrer">Buy Now</a>
    </main>
</body>
</html>
