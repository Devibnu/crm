<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Krakatau CRM')</title>
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}?v={{ filemtime(public_path('css/admin-dashboard.css')) }}">
</head>
<body data-sidebar-variant="small-clean">
    <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle" aria-hidden="true">
    <label for="sidebar-toggle" class="sidebar-backdrop" aria-label="Close navigation"></label>
    @include('admin.partials.sidebar')

    <main class="app-shell">
        @include('admin.partials.topbar')
        @yield('content')
        <button class="settings" type="button" aria-label="Settings" aria-expanded="false" aria-controls="quick-settings-panel" data-settings-button data-title-en="Settings" data-title-id="Pengaturan"><svg viewBox="0 0 24 24"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 8.6 19a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 5 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.1A1.7 1.7 0 0 0 15.4 5a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 .6 1 1.7 1.7 0 0 0 1.1.4h.1a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.7.6Z"/></svg></button>
    </main>
    <div class="settings-backdrop" data-settings-backdrop hidden></div>
    <aside class="settings-panel" id="quick-settings-panel" aria-hidden="true" hidden>
        <div class="settings-panel-head">
            <div>
                <strong data-lang-en="Quick Settings" data-lang-id="Pengaturan Cepat">Quick Settings</strong>
                <small data-lang-en="Adjust admin workspace preferences." data-lang-id="Atur preferensi workspace admin.">Atur preferensi workspace admin.</small>
            </div>
            <button type="button" class="settings-panel-close" aria-label="Close quick settings" data-settings-close data-title-en="Close quick settings" data-title-id="Tutup pengaturan cepat">
                <svg viewBox="0 0 24 24"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="settings-panel-section">
            <span data-lang-en="Theme" data-lang-id="Tema">Theme</span>
            <div class="settings-choice-grid">
                <button type="button" class="settings-choice-card" data-settings-theme="light">
                    <strong data-lang-en="Light" data-lang-id="Terang">Light</strong>
                    <small data-lang-en="Bright appearance for the dashboard." data-lang-id="Tampilan terang untuk dashboard.">Tampilan terang untuk dashboard.</small>
                </button>
                <button type="button" class="settings-choice-card" data-settings-theme="dark">
                    <strong data-lang-en="Dark" data-lang-id="Gelap">Dark</strong>
                    <small data-lang-en="Dark appearance with softer contrast." data-lang-id="Tampilan gelap dengan kontras lembut.">Tampilan gelap dengan kontras lembut.</small>
                </button>
            </div>
        </div>
        <div class="settings-panel-section">
            <span data-lang-en="Language" data-lang-id="Bahasa">Language</span>
            <div class="settings-choice-grid">
                <button type="button" class="settings-choice-card" data-settings-language="id">
                    <strong data-lang-en="Bahasa Indonesia" data-lang-id="Bahasa Indonesia">Bahasa Indonesia</strong>
                    <small data-lang-en="Indonesian interface labels." data-lang-id="Label antarmuka Indonesia.">Label antarmuka Indonesia.</small>
                </button>
                <button type="button" class="settings-choice-card" data-settings-language="en">
                    <strong data-lang-en="English" data-lang-id="Inggris">English</strong>
                    <small data-lang-en="Use English admin labels." data-lang-id="Gunakan label admin bahasa Inggris.">Use English admin labels.</small>
                </button>
            </div>
        </div>
        <div class="settings-panel-section">
            <span data-lang-en="Sidebar Menu" data-lang-id="Menu Sidebar">Sidebar Menu</span>
            <div class="settings-choice-grid">
                <button type="button" class="settings-choice-card" data-settings-sidebar-variant="small-clean">
                    <strong data-lang-en="Small Clean" data-lang-id="Kecil Rapi">Small Clean</strong>
                    <small data-lang-en="Small, tidy, and easy to read for daily use." data-lang-id="Kecil, rapi, dan mudah dibaca untuk penggunaan harian.">Kecil, rapi, dan mudah dibaca untuk penggunaan harian.</small>
                </button>
                <button type="button" class="settings-choice-card" data-settings-sidebar-variant="small-compact">
                    <strong data-lang-en="Small Compact" data-lang-id="Kecil Ringkas">Small Compact</strong>
                    <small data-lang-en="Tighter spacing for long menus and denser layouts." data-lang-id="Lebih rapat untuk menu panjang dan kepadatan tinggi.">Lebih rapat untuk menu panjang dan kepadatan tinggi.</small>
                </button>
            </div>
        </div>
        <div class="settings-panel-section">
            <span data-lang-en="Quick Actions" data-lang-id="Aksi Cepat">Quick Actions</span>
            <div class="settings-action-stack">
                <button type="button" class="settings-action-button" data-settings-focus-search>
                    <strong data-lang-en="Focus Search" data-lang-id="Fokus Pencarian">Focus Search</strong>
                    <small data-lang-en="Activate quick search with `Ctrl/Cmd + K`." data-lang-id="Aktifkan pencarian cepat `Ctrl/Cmd + K`.">Aktifkan pencarian cepat `Ctrl/Cmd + K`.</small>
                </button>
                <button type="button" class="settings-action-button" data-settings-reset>
                    <strong data-lang-en="Reset Preferences" data-lang-id="Reset Preferensi">Reset Preferences</strong>
                    <small data-lang-en="Restore theme and language to browser defaults." data-lang-id="Kembalikan theme dan language ke default browser.">Kembalikan theme dan language ke default browser.</small>
                </button>
            </div>
        </div>
    </aside>
    <script>
        (() => {
            const topbar = document.querySelector('[data-topbar]');

            if (!topbar) {
                return;
            }

            const body = document.body;
            const html = document.documentElement;
            const searchInput = document.querySelector('[data-topbar-search]');
            const searchWrap = document.querySelector('[data-topbar-search-wrap]');
            const searchPanel = document.querySelector('[data-topbar-search-panel]');
            const searchResults = Array.from(document.querySelectorAll('[data-search-item]'));
            const searchEmpty = document.querySelector('[data-topbar-search-empty]');
            const searchShortcut = document.querySelector('[data-search-shortcut]');
            const searchPanelTitle = document.querySelector('[data-search-panel-title]');
            const searchPanelSubtitle = document.querySelector('[data-search-panel-subtitle]');
            const languageLabel = document.querySelector('[data-current-language-label]');
            const themeButton = document.querySelector('[data-theme-toggle]');
            const languageButtons = document.querySelectorAll('[data-language-option]');
            const dropdownTriggers = document.querySelectorAll('[data-topbar-trigger]');
            const settingsButton = document.querySelector('[data-settings-button]');
            const settingsPanel = document.getElementById('quick-settings-panel');
            const settingsBackdrop = document.querySelector('[data-settings-backdrop]');
            const settingsCloseButton = document.querySelector('[data-settings-close]');
            const settingsThemeButtons = document.querySelectorAll('[data-settings-theme]');
            const settingsLanguageButtons = document.querySelectorAll('[data-settings-language]');
            const settingsSidebarVariantButtons = document.querySelectorAll('[data-settings-sidebar-variant]');
            const settingsFocusSearchButton = document.querySelector('[data-settings-focus-search]');
            const settingsResetButton = document.querySelector('[data-settings-reset]');
            const isMac = /Mac|iPhone|iPad|iPod/.test(window.navigator.platform);
            const storageKeys = {
                theme: 'crm-admin-theme',
                language: 'crm-admin-language',
                sidebarVariant: 'crm-admin-sidebar-variant',
            };

            const copy = {
                id: {
                    searchPlaceholder: 'Cari modul CRM',
                    searchPanelTitle: 'Pencarian Cepat',
                    searchPanelSubtitle: 'Ketik nama modul lalu tekan Enter.',
                    searchEmpty: 'Tidak ada modul yang cocok.',
                    languageLabel: 'Bahasa antarmuka',
                    themeLightTitle: 'Pakai tema terang',
                    themeDarkTitle: 'Pakai tema gelap',
                },
                en: {
                    searchPlaceholder: 'Search CRM modules',
                    searchPanelTitle: 'Quick Search',
                    searchPanelSubtitle: 'Type a module name then press Enter.',
                    searchEmpty: 'No matching module found.',
                    languageLabel: 'Interface language',
                    themeLightTitle: 'Switch to light theme',
                    themeDarkTitle: 'Switch to dark theme',
                },
            };

            const getVisibleSearchResults = () => searchResults.filter(item => !item.hidden);

            const applyStaticTranslations = language => {
                document.querySelectorAll('[data-lang-en][data-lang-id]').forEach(node => {
                    node.textContent = language === 'en' ? node.dataset.langEn : node.dataset.langId;
                });

                document.querySelectorAll('[data-placeholder-en][data-placeholder-id]').forEach(node => {
                    node.setAttribute('placeholder', language === 'en' ? node.dataset.placeholderEn : node.dataset.placeholderId);
                });

                document.querySelectorAll('[data-doc-title-en][data-doc-title-id]').forEach(node => {
                    document.title = language === 'en' ? node.dataset.docTitleEn : node.dataset.docTitleId;
                });

                document.querySelectorAll('[data-confirm-en][data-confirm-id]').forEach(node => {
                    node.dataset.confirmCurrent = language === 'en' ? node.dataset.confirmEn : node.dataset.confirmId;
                });

                document.querySelectorAll('[data-title-en][data-title-id]').forEach(node => {
                    const title = language === 'en' ? node.dataset.titleEn : node.dataset.titleId;
                    node.setAttribute('title', title);

                    if (node.hasAttribute('aria-label')) {
                        node.setAttribute('aria-label', title);
                    }
                });
            };

            const closeSearchPanel = () => {
                if (searchPanel) {
                    searchPanel.hidden = true;
                }
            };

            const closeSettingsPanel = () => {
                if (settingsPanel) {
                    settingsPanel.hidden = true;
                    settingsPanel.setAttribute('aria-hidden', 'true');
                }

                if (settingsBackdrop) {
                    settingsBackdrop.hidden = true;
                }

                if (settingsButton) {
                    settingsButton.setAttribute('aria-expanded', 'false');
                }
            };

            const openSettingsPanel = () => {
                if (!settingsPanel) {
                    return;
                }

                closeMenus(null);
                closeSearchPanel();
                settingsPanel.hidden = false;
                settingsPanel.setAttribute('aria-hidden', 'false');

                if (settingsBackdrop) {
                    settingsBackdrop.hidden = false;
                }

                if (settingsButton) {
                    settingsButton.setAttribute('aria-expanded', 'true');
                }
            };

            const filterSearchResults = query => {
                const normalizedQuery = (query || '').trim().toLowerCase();
                let visibleCount = 0;

                searchResults.forEach(item => {
                    const matches = normalizedQuery === ''
                        || (item.dataset.searchKeywords || '').includes(normalizedQuery);

                    item.hidden = !matches;

                    if (matches) {
                        visibleCount += 1;
                    }
                });

                if (searchEmpty) {
                    searchEmpty.hidden = visibleCount !== 0;
                }
            };

            const openSearchPanel = ({ focusInput = false, selectInput = false } = {}) => {
                if (!searchPanel || !searchInput) {
                    return;
                }

                closeMenus(null);
                filterSearchResults(searchInput.value);
                searchPanel.hidden = false;

                if (focusInput) {
                    searchInput.focus();
                }

                if (selectInput) {
                    searchInput.select();
                }
            };

            const closeMenus = currentMenu => {
                document.querySelectorAll('.topbar-menu').forEach(menu => {
                    const isActive = currentMenu && menu === currentMenu;
                    menu.hidden = !isActive;
                });

                document.querySelectorAll('[data-topbar-trigger]').forEach(trigger => {
                    const targetId = trigger.getAttribute('aria-controls');
                    const controlledMenu = targetId ? document.getElementById(targetId) : null;
                    const isExpanded = controlledMenu && !controlledMenu.hidden;

                    trigger.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                });
            };

            const applyTheme = requestedTheme => {
                const resolvedTheme = requestedTheme === 'dark' ? 'dark' : 'light';
                const activeLanguage = html.lang === 'en' ? 'en' : 'id';
                const themeCopy = copy[activeLanguage];

                body.dataset.theme = resolvedTheme;
                html.style.colorScheme = resolvedTheme;
                localStorage.setItem(storageKeys.theme, resolvedTheme);

                if (themeButton) {
                    const nextLabel = resolvedTheme === 'dark' ? themeCopy.themeLightTitle : themeCopy.themeDarkTitle;
                    themeButton.setAttribute('aria-pressed', resolvedTheme === 'dark' ? 'true' : 'false');
                    themeButton.setAttribute('title', nextLabel);
                    themeButton.setAttribute('aria-label', nextLabel);
                }

                settingsThemeButtons.forEach(button => {
                    button.classList.toggle('is-active', button.dataset.settingsTheme === resolvedTheme);
                });
            };

            const applyLanguage = requestedLanguage => {
                const resolvedLanguage = requestedLanguage === 'en' ? 'en' : 'id';
                const currentCopy = copy[resolvedLanguage];

                html.lang = resolvedLanguage;
                localStorage.setItem(storageKeys.language, resolvedLanguage);

                if (searchInput) {
                    searchInput.placeholder = currentCopy.searchPlaceholder;
                }

                if (searchPanelTitle) {
                    searchPanelTitle.textContent = currentCopy.searchPanelTitle;
                }

                if (searchPanelSubtitle) {
                    searchPanelSubtitle.textContent = currentCopy.searchPanelSubtitle;
                }

                if (searchEmpty) {
                    searchEmpty.textContent = currentCopy.searchEmpty;
                }

                if (languageLabel) {
                    languageLabel.textContent = currentCopy.languageLabel;
                }

                languageButtons.forEach(button => {
                    button.classList.toggle('is-active', button.dataset.languageOption === resolvedLanguage);
                });

                settingsLanguageButtons.forEach(button => {
                    button.classList.toggle('is-active', button.dataset.settingsLanguage === resolvedLanguage);
                });

                applyStaticTranslations(resolvedLanguage);
                document.dispatchEvent(new CustomEvent('crm:language-changed', {
                    detail: { language: resolvedLanguage },
                }));
                applyTheme(body.dataset.theme || 'light');
            };

            const applySidebarVariant = requestedVariant => {
                const resolvedVariant = requestedVariant === 'small-compact' ? 'small-compact' : 'small-clean';

                body.dataset.sidebarVariant = resolvedVariant;
                localStorage.setItem(storageKeys.sidebarVariant, resolvedVariant);

                settingsSidebarVariantButtons.forEach(button => {
                    button.classList.toggle('is-active', button.dataset.settingsSidebarVariant === resolvedVariant);
                });
            };

            const storedTheme = localStorage.getItem(storageKeys.theme)
                || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            const storedLanguage = localStorage.getItem(storageKeys.language) || html.lang || 'id';
            const storedSidebarVariant = localStorage.getItem(storageKeys.sidebarVariant) || body.dataset.sidebarVariant || 'small-clean';

            if (searchShortcut) {
                searchShortcut.textContent = isMac ? 'Cmd K' : 'Ctrl K';
            }

            applyTheme(storedTheme);
            applyLanguage(storedLanguage);
            applySidebarVariant(storedSidebarVariant);

            themeButton?.addEventListener('click', () => {
                applyTheme(body.dataset.theme === 'dark' ? 'light' : 'dark');
            });

            settingsThemeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    applyTheme(button.dataset.settingsTheme || 'light');
                });
            });

            languageButtons.forEach(button => {
                button.addEventListener('click', () => {
                    applyLanguage(button.dataset.languageOption || 'id');
                    closeMenus(null);
                });
            });

            settingsLanguageButtons.forEach(button => {
                button.addEventListener('click', () => {
                    applyLanguage(button.dataset.settingsLanguage || 'id');
                });
            });

            settingsSidebarVariantButtons.forEach(button => {
                button.addEventListener('click', () => {
                    applySidebarVariant(button.dataset.settingsSidebarVariant || 'small-clean');
                });
            });

            dropdownTriggers.forEach(trigger => {
                trigger.addEventListener('click', event => {
                    const targetId = trigger.getAttribute('aria-controls');
                    const targetMenu = targetId ? document.getElementById(targetId) : null;

                    if (!targetMenu) {
                        return;
                    }

                    event.stopPropagation();
                    const shouldOpen = targetMenu.hidden;

                    closeSearchPanel();
                    closeMenus(shouldOpen ? targetMenu : null);
                });
            });

            settingsButton?.addEventListener('click', event => {
                event.stopPropagation();

                if (settingsPanel?.hidden) {
                    openSettingsPanel();
                } else {
                    closeSettingsPanel();
                }
            });

            settingsBackdrop?.addEventListener('click', closeSettingsPanel);
            settingsCloseButton?.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                closeSettingsPanel();
            });

            settingsPanel?.addEventListener('click', event => {
                const closeTrigger = event.target.closest('[data-settings-close]');

                if (closeTrigger) {
                    event.preventDefault();
                    event.stopPropagation();
                    closeSettingsPanel();
                }
            });

            settingsFocusSearchButton?.addEventListener('click', () => {
                closeSettingsPanel();
                openSearchPanel({ focusInput: true, selectInput: true });
            });

            settingsResetButton?.addEventListener('click', () => {
                localStorage.removeItem(storageKeys.theme);
                localStorage.removeItem(storageKeys.language);
                localStorage.removeItem(storageKeys.sidebarVariant);

                const browserTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                const browserLanguage = (navigator.language || '').toLowerCase().startsWith('en') ? 'en' : 'id';

                applyTheme(browserTheme);
                applyLanguage(browserLanguage);
                applySidebarVariant('small-clean');
            });

            document.addEventListener('click', event => {
                const clickedInsideTopbar = topbar.contains(event.target);
                const clickedInsideSettings = settingsPanel?.contains(event.target);
                const clickedSettingsButton = settingsButton?.contains(event.target);

                if (!clickedInsideTopbar) {
                    closeMenus(null);
                    closeSearchPanel();
                }

                if (!clickedInsideSettings && !clickedSettingsButton) {
                    closeSettingsPanel();
                }
            });

            searchInput?.addEventListener('focus', () => {
                openSearchPanel();
            });

            searchInput?.addEventListener('input', () => {
                openSearchPanel();
            });

            searchInput?.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    const firstResult = getVisibleSearchResults()[0];

                    if (firstResult) {
                        event.preventDefault();
                        window.location.href = firstResult.getAttribute('href') || '#';
                    }
                }

                if (event.key === 'Escape') {
                    closeSearchPanel();
                    searchInput.blur();
                }
            });

            window.addEventListener('keydown', event => {
                if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                    event.preventDefault();
                    event.stopPropagation();
                    openSearchPanel({ focusInput: true, selectInput: true });
                }

                if (event.key === 'Escape') {
                    closeMenus(null);
                    closeSearchPanel();
                    closeSettingsPanel();
                }
            }, true);
        })();
    </script>
</body>
</html>
