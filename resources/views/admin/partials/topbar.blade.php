@php
    $user = auth()->user();
    $uiTranslations = [
        'CRM User' => ['en' => 'CRM User', 'id' => 'Pengguna CRM'],
        'Dashboards' => ['en' => 'Dashboards', 'id' => 'Dashboard'],
        'Sales' => ['en' => 'Sales', 'id' => 'Sales'],
        'Service' => ['en' => 'Service', 'id' => 'Layanan'],
        'Marketing' => ['en' => 'Marketing', 'id' => 'Marketing'],
        'Customers' => ['en' => 'Customers', 'id' => 'Customer'],
        'System' => ['en' => 'System', 'id' => 'Sistem'],
        'Quick Search' => ['en' => 'Quick Search', 'id' => 'Pencarian Cepat'],
        'Type a module name then press Enter.' => ['en' => 'Type a module name then press Enter.', 'id' => 'Ketik nama modul lalu tekan Enter.'],
        'No matching modules found.' => ['en' => 'No matching modules found.', 'id' => 'Tidak ada modul yang cocok.'],
        'CRM Overview' => ['en' => 'CRM Overview', 'id' => 'Ringkasan CRM'],
        'Sales Enablement' => ['en' => 'Sales Enablement', 'id' => 'Sales Enablement'],
        'Service Management' => ['en' => 'Service Management', 'id' => 'Manajemen Layanan'],
        'Marketing Automation' => ['en' => 'Marketing Automation', 'id' => 'Otomasi Marketing'],
        'Customer Profile 360' => ['en' => 'Customer Profile 360', 'id' => 'Profil Customer 360'],
        'User & Roles' => ['en' => 'User & Roles', 'id' => 'User & Role'],
        'Omnichannel Inbox' => ['en' => 'Omnichannel Inbox', 'id' => 'Inbox Omnichannel'],
        'Quick apps' => ['en' => 'Quick apps', 'id' => 'Aplikasi cepat'],
        'Notifications' => ['en' => 'Notifications', 'id' => 'Notifikasi'],
        'Profile' => ['en' => 'Profile', 'id' => 'Profil'],
        'Logout' => ['en' => 'Logout', 'id' => 'Keluar'],
        'CRM overview terbaru' => ['en' => 'Latest CRM overview', 'id' => 'CRM overview terbaru'],
        'Pantau revenue, lead, pipeline, SLA, dan campaign dari satu workspace.' => ['en' => 'Monitor revenue, leads, pipeline, SLA, and campaigns from one workspace.', 'id' => 'Pantau revenue, lead, pipeline, SLA, dan campaign dari satu workspace.'],
        'Review sales pipeline' => ['en' => 'Review sales pipeline', 'id' => 'Review sales pipeline'],
        'Cek open, qualified, proposal, dan weighted forecast terbaru.' => ['en' => 'Check the latest open, qualified, proposal, and weighted forecast.', 'id' => 'Cek open, qualified, proposal, dan weighted forecast terbaru.'],
        'Omnichannel inbox aktif' => ['en' => 'Active omnichannel inbox', 'id' => 'Omnichannel inbox aktif'],
        'Lihat percakapan baru dan ticket yang perlu follow-up.' => ['en' => 'See new conversations and tickets that need follow-up.', 'id' => 'Lihat percakapan baru dan ticket yang perlu follow-up.'],
        'Marketing automation' => ['en' => 'Marketing automation', 'id' => 'Otomasi marketing'],
        'Pantau campaign, execution, dan lead scoring terbaru.' => ['en' => 'Monitor the latest campaigns, executions, and lead scoring.', 'id' => 'Pantau campaign, execution, dan lead scoring terbaru.'],
        'Customer profile insight' => ['en' => 'Customer profile insight', 'id' => 'Insight profil customer'],
        'Lihat interaction, transaction, dan behavior update customer.' => ['en' => 'See customer interaction, transaction, and behavior updates.', 'id' => 'Lihat interaction, transaction, dan behavior update customer.'],
        'Cari modul CRM' => ['en' => 'Search CRM modules', 'id' => 'Cari modul CRM'],
        'Bahasa antarmuka' => ['en' => 'Interface language', 'id' => 'Bahasa antarmuka'],
        'quick actions available' => ['en' => 'quick actions available', 'id' => 'aksi cepat tersedia'],
        'Admin profile' => ['en' => 'Admin profile', 'id' => 'Profil admin'],
        'Admin' => ['en' => 'Admin', 'id' => 'Admin'],
        'admin@localhost' => ['en' => 'admin@localhost', 'id' => 'admin@localhost'],
    ];
    $translateUi = static function (?string $text) use ($uiTranslations): array {
        $text = trim((string) $text);

        if ($text === '') {
            return ['en' => '', 'id' => ''];
        }

        return $uiTranslations[$text] ?? ['en' => $text, 'id' => $text];
    };
    $roleNames = method_exists($user, 'getRoleNames')
        ? $user->getRoleNames()->map(fn ($role) => str($role)->replace('_', ' ')->headline()->toString())
        : collect();
    $roleLabelText = $roleNames->implode(', ') ?: 'CRM User';
    $roleLabel = $translateUi($roleLabelText);
    $visibleDashboardMenu = \App\Support\DashboardAccess::visibleMenuItems($dashboardMenu ?? [], $user);
    $canSeeProfileLinks = (bool) ($user?->hasAnyRole(['super_admin', 'admin']) ?? false);
    $initials = collect(preg_split('/\s+/', trim((string) ($user?->name ?? 'Admin'))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => str($part)->substr(0, 1)->upper()->toString())
        ->implode('');

    $canAccessTopbarItem = static function (array $item) use ($user): bool {
        if (isset($item['dashboard_title']) && ! \App\Support\DashboardAccess::canAccess($user, (string) $item['dashboard_title'])) {
            return false;
        }

        $routeName = (string) ($item['route'] ?? '');
        $needsSystemRole = str($routeName)->startsWith('admin.system.');

        if ($needsSystemRole && ! ($user?->hasAnyRole(['super_admin', 'admin']) ?? false)) {
            return false;
        }

        if (! isset($item['permission']) || $item['permission'] === null || $item['permission'] === '') {
            return true;
        }

        return (bool) ($user?->can($item['permission']) ?? false);
    };

    $topbarAppGroups = collect([
        ['title' => 'Dashboards', 'items' => collect($visibleDashboardMenu)->take(5)],
        ['title' => 'Sales', 'items' => collect($salesMenu ?? [])->take(4)],
        ['title' => 'Service', 'items' => collect($serviceMenu ?? [])->take(4)],
        ['title' => 'Marketing', 'items' => collect($marketingMenu ?? [])->take(4)],
        ['title' => 'Customers', 'items' => collect($customersMenu ?? [])->take(4)],
        ['title' => 'System', 'items' => collect($systemMenu ?? [])->take(2)],
    ])
        ->map(function (array $group) use ($canAccessTopbarItem) {
            $group['items'] = collect($group['items'])->filter($canAccessTopbarItem)->values();

            return $group;
        })
        ->filter(fn (array $group) => $group['items']->isNotEmpty())
        ->map(function (array $group) use ($translateUi) {
            $group['title_translation'] = $translateUi((string) $group['title']);

            return $group;
        })
        ->values();

    $topbarQuickApps = $topbarAppGroups
        ->flatMap(function (array $group) use ($translateUi) {
            return $group['items']->take(8)->map(function (array $item) use ($group, $translateUi) {
                $routeLabel = str((string) $item['route'])->afterLast('.')->replace(['-', '_'], ' ')->headline()->toString();

                return array_merge($item, [
                    'title_translation' => $translateUi((string) ($item['title'] ?? '')),
                    'meta_translation' => $translateUi($routeLabel),
                    'group_translation' => $group['title_translation'],
                ]);
            });
        })
        ->take(8)
        ->values();

    $topbarNotifications = collect([
        [
            'icon' => 'dashboard',
            'title' => 'CRM overview terbaru',
            'description' => 'Pantau revenue, lead, pipeline, SLA, dan campaign dari satu workspace.',
            'href' => route('admin.dashboard'),
            'dashboard_title' => 'CRM Overview',
        ],
        [
            'icon' => 'pipeline',
            'title' => 'Review sales pipeline',
            'description' => 'Cek open, qualified, proposal, dan weighted forecast terbaru.',
            'href' => route('admin.dashboard.sales'),
            'dashboard_title' => 'Sales Enablement',
            'permission' => 'pipeline.view',
        ],
        [
            'icon' => 'inbox',
            'title' => 'Omnichannel inbox aktif',
            'description' => 'Lihat percakapan baru dan ticket yang perlu follow-up.',
            'href' => route('admin.service.omnichannel.index'),
            'permission' => 'omnichannel.view',
        ],
        [
            'icon' => 'campaign',
            'title' => 'Marketing automation',
            'description' => 'Pantau campaign, execution, dan lead scoring terbaru.',
            'href' => route('admin.dashboard.marketing'),
            'dashboard_title' => 'Marketing Automation',
            'permission' => 'campaigns.view',
        ],
        [
            'icon' => 'user',
            'title' => 'Customer profile insight',
            'description' => 'Lihat interaction, transaction, dan behavior update customer.',
            'href' => route('admin.dashboard.customer'),
            'dashboard_title' => 'Customer Profile 360',
            'permission' => 'customers.view',
        ],
    ])->filter($canAccessTopbarItem)
        ->map(function (array $notification) use ($translateUi) {
            $notification['title_translation'] = $translateUi((string) $notification['title']);
            $notification['description_translation'] = $translateUi((string) $notification['description']);

            return $notification;
        })
        ->take(4)
        ->values();

    $profileLinks = $canSeeProfileLinks ? collect([
        [
            'icon' => 'dashboard',
            'label' => 'CRM Overview',
            'href' => route('admin.dashboard'),
            'dashboard_title' => 'CRM Overview',
        ],
        [
            'icon' => 'user',
            'label' => 'Customer Profile 360',
            'href' => route('admin.dashboard.customer'),
            'dashboard_title' => 'Customer Profile 360',
            'permission' => 'customers.view',
        ],
        [
            'icon' => 'lock',
            'label' => 'User & Roles',
            'href' => route('admin.system.users.index'),
        ],
    ])->filter($canAccessTopbarItem)
        ->map(function (array $link) use ($translateUi) {
            $link['label_translation'] = $translateUi((string) $link['label']);

            return $link;
        })
        ->values() : collect();

    $topbarSearchItems = $topbarAppGroups
        ->flatMap(function (array $group) use ($translateUi) {
            return $group['items']->map(function (array $item) use ($group) {
                return [
                    'title' => $item['title'],
                    'icon' => $item['icon'],
                    'href' => route($item['route']),
                    'group' => $group['title'],
                    'keywords' => str(implode(' ', [
                        $group['title'],
                        $item['title'],
                        $item['route'],
                        $item['icon'],
                    ]))
                        ->replace(['.', '-', '_'], ' ')
                        ->lower()
                        ->toString(),
                ];
            });
        })
        ->map(function (array $item) use ($translateUi) {
            $item['title_translation'] = $translateUi((string) $item['title']);
            $item['group_translation'] = $translateUi((string) $item['group']);

            return $item;
        })
        ->unique('href')
        ->values();
@endphp

<header class="topbar">
    <label for="sidebar-toggle" class="nav-toggle-button" aria-label="Open navigation">
        <svg viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
    </label>

    <div class="search" data-topbar-search-wrap>
        <span class="search-icon">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        </span>
        <input type="search" placeholder="Cari modul CRM" data-topbar-search data-placeholder-en="Search CRM modules" data-placeholder-id="Cari modul CRM">
        <kbd data-search-shortcut>Cmd K</kbd>
        <div class="topbar-search-panel" data-topbar-search-panel hidden>
            <div class="topbar-search-head">
                <strong data-search-panel-title data-lang-en="Quick Search" data-lang-id="Pencarian Cepat">Quick Search</strong>
                <small data-search-panel-subtitle data-lang-en="Type a module name then press Enter." data-lang-id="Ketik nama modul lalu tekan Enter.">Ketik nama modul lalu tekan Enter.</small>
            </div>
            <div class="topbar-search-results" data-topbar-search-results>
                @foreach ($topbarSearchItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        class="topbar-search-result"
                        data-search-item
                        data-search-keywords="{{ $item['keywords'] }}"
                    >
                        <span class="topbar-search-result-icon">@include('admin.partials.sidebar-icon', ['icon' => $item['icon']])</span>
                        <span class="topbar-search-result-copy">
                            <strong data-lang-en="{{ $item['title_translation']['en'] }}" data-lang-id="{{ $item['title_translation']['id'] }}">{{ $item['title_translation']['en'] }}</strong>
                            <small data-lang-en="{{ $item['group_translation']['en'] }}" data-lang-id="{{ $item['group_translation']['id'] }}">{{ $item['group_translation']['en'] }}</small>
                        </span>
                    </a>
                @endforeach
                <div class="topbar-search-empty" data-topbar-search-empty hidden data-lang-en="No matching modules found." data-lang-id="Tidak ada modul yang cocok.">Tidak ada modul yang cocok.</div>
            </div>
        </div>
    </div>

    <div class="top-actions" data-topbar>
        <div class="topbar-action" data-topbar-action>
            <button type="button" class="topbar-action-trigger" aria-label="Language" title="Language" aria-expanded="false" aria-controls="topbar-language-menu" data-topbar-trigger>
                <svg viewBox="0 0 24 24"><path d="M4 5h9"/><path d="M9 3v2"/><path d="M6 5c.7 3.7 3 6.3 7 8"/><path d="M12 5c-.7 3.4-3 6.2-7 8"/><path d="M14 19l3-7 3 7"/><path d="M15 17h4"/></svg>
            </button>
            <div class="topbar-menu topbar-menu--compact" id="topbar-language-menu" hidden>
                <div class="topbar-menu-head">
                    <strong data-lang-en="Language" data-lang-id="Bahasa">Language</strong>
                    <small data-current-language-label>Bahasa antarmuka</small>
                </div>
                <div class="topbar-menu-stack">
                    <button type="button" class="topbar-language-option" data-language-option="id">
                        <span>
                            <strong data-lang-en="Bahasa Indonesia" data-lang-id="Bahasa Indonesia">Bahasa Indonesia</strong>
                            <small data-lang-en="Use Indonesian interface labels." data-lang-id="Gunakan label antarmuka Indonesia.">Gunakan label antarmuka Indonesia.</small>
                        </span>
                        <i aria-hidden="true"></i>
                    </button>
                    <button type="button" class="topbar-language-option" data-language-option="en">
                        <span>
                            <strong data-lang-en="English" data-lang-id="Inggris">English</strong>
                            <small data-lang-en="Use English labels for the admin shell." data-lang-id="Gunakan label bahasa Inggris untuk shell admin.">Use English labels for the admin shell.</small>
                        </span>
                        <i aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        <button type="button" class="topbar-action-trigger topbar-theme-toggle" aria-label="Theme" title="Toggle theme" aria-pressed="false" data-theme-toggle>
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
        </button>

        <div class="topbar-action" data-topbar-action>
            <button type="button" class="topbar-action-trigger" aria-label="Apps" title="Quick apps" aria-expanded="false" aria-controls="topbar-apps-menu" data-topbar-trigger>
                <svg viewBox="0 0 24 24"><path d="M4 4h6v6H4z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6H4z"/><path d="M14 14h6v6h-6z"/></svg>
            </button>
            <div class="topbar-menu topbar-menu--apps" id="topbar-apps-menu" hidden>
                <div class="topbar-menu-head">
                    <strong data-lang-en="Quick Apps" data-lang-id="Aplikasi Cepat">Quick Apps</strong>
                    <small data-lang-en="Quick access to key CRM modules." data-lang-id="Akses cepat ke modul utama CRM.">Akses cepat ke modul utama CRM.</small>
                </div>
                <div class="topbar-quick-grid">
                    @foreach ($topbarQuickApps as $app)
                        <a href="{{ route($app['route']) }}" class="topbar-quick-link">
                            <span class="topbar-quick-icon">@include('admin.partials.sidebar-icon', ['icon' => $app['icon']])</span>
                            <strong data-lang-en="{{ $app['title_translation']['en'] }}" data-lang-id="{{ $app['title_translation']['id'] }}">{{ $app['title_translation']['en'] }}</strong>
                            <small data-lang-en="{{ $app['meta_translation']['en'] }}" data-lang-id="{{ $app['meta_translation']['id'] }}">{{ $app['meta_translation']['en'] }}</small>
                        </a>
                    @endforeach
                </div>

                @if ($topbarAppGroups->isNotEmpty())
                    <div class="topbar-menu-divider"></div>
                    <div class="topbar-menu-list">
                        @foreach ($topbarAppGroups as $group)
                            <div class="topbar-group-row">
                                <span data-lang-en="{{ $group['title_translation']['en'] }}" data-lang-id="{{ $group['title_translation']['id'] }}">{{ $group['title_translation']['en'] }}</span>
                                <div>
                                    @foreach ($group['items']->take(3) as $item)
                                        @php($itemTranslation = $translateUi((string) $item['title']))
                                        <a href="{{ route($item['route']) }}" data-lang-en="{{ $itemTranslation['en'] }}" data-lang-id="{{ $itemTranslation['id'] }}">{{ $itemTranslation['en'] }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="topbar-action" data-topbar-action>
            <button type="button" class="topbar-action-trigger notification" aria-label="Notifications" title="Notifications" aria-expanded="false" aria-controls="topbar-notifications-menu" data-topbar-trigger>
                <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
            </button>
            <div class="topbar-menu topbar-menu--notifications" id="topbar-notifications-menu" hidden>
                <div class="topbar-menu-head">
                    <strong data-lang-en="Notifications" data-lang-id="Notifikasi">Notifications</strong>
                    <small><span>{{ $topbarNotifications->count() }}</span> <span data-lang-en="quick actions available" data-lang-id="aksi cepat tersedia">quick actions available</span></small>
                </div>
                <div class="topbar-menu-stack">
                    @forelse ($topbarNotifications as $notification)
                        <a href="{{ $notification['href'] }}" class="topbar-notification-item">
                            <span class="topbar-notification-icon">@include('admin.partials.sidebar-icon', ['icon' => $notification['icon']])</span>
                            <span class="topbar-notification-copy">
                                <strong data-lang-en="{{ $notification['title_translation']['en'] }}" data-lang-id="{{ $notification['title_translation']['id'] }}">{{ $notification['title_translation']['en'] }}</strong>
                                <small data-lang-en="{{ $notification['description_translation']['en'] }}" data-lang-id="{{ $notification['description_translation']['id'] }}">{{ $notification['description_translation']['en'] }}</small>
                            </span>
                        </a>
                    @empty
                        <div class="topbar-empty-state" data-lang-en="No new notifications." data-lang-id="Tidak ada notifikasi baru.">Tidak ada notifikasi baru.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="topbar-action" data-topbar-action>
            <button type="button" class="topbar-avatar-button" aria-label="Profile" title="Profile" aria-expanded="false" aria-controls="topbar-profile-menu" data-topbar-trigger>
                <span class="topbar-avatar-image">
                    <img src="{{ asset('assets/avatars/avatar-1.png') }}" alt="{{ $user?->name ?? 'Admin profile' }}">
                </span>
                <span class="topbar-avatar-fallback">{{ $initials }}</span>
            </button>
            <div class="topbar-menu topbar-menu--profile" id="topbar-profile-menu" hidden>
                <div class="topbar-profile-head">
                    <span class="topbar-avatar-large">{{ $initials }}</span>
                    <div class="topbar-profile-meta">
                        <strong>{{ $user?->name ?? 'Admin' }}</strong>
                        <small>{{ $user?->email ?? 'admin@localhost' }}</small>
                        <span data-lang-en="{{ $roleLabel['en'] }}" data-lang-id="{{ $roleLabel['id'] }}">{{ $roleLabel['en'] }}</span>
                    </div>
                </div>
                @if ($profileLinks->isNotEmpty())
                    <div class="topbar-menu-list">
                        @foreach ($profileLinks as $link)
                            <a href="{{ $link['href'] }}" class="topbar-menu-link">
                                <span>@include('admin.partials.sidebar-icon', ['icon' => $link['icon']])</span>
                                <strong data-lang-en="{{ $link['label_translation']['en'] }}" data-lang-id="{{ $link['label_translation']['id'] }}">{{ $link['label_translation']['en'] }}</strong>
                            </a>
                        @endforeach
                    </div>
                @endif
                <div class="topbar-menu-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="topbar-profile-logout">
                        <span><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M21 3v18"/></svg></span>
                        <strong data-lang-en="Logout" data-lang-id="Keluar">Logout</strong>
                    </button>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="topbar-action-trigger" aria-label="Logout" title="Logout">
                <svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M21 3v18"/></svg>
            </button>
        </form>
    </div>
</header>
