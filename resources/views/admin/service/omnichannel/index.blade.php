@extends('admin.layouts.app')

@section('title', 'Omnichannel Inbox - Krakatau CRM')

@section('content')
    @php($activeConversation = $selectedConversation)
    @php($activeMessages = $activeConversation?->messages?->sortBy('created_at') ?? collect())
    @php($activeCustomer = $customerWorkspace['customer'] ?? null)
    @php($activeLead = $customerWorkspace['lead'] ?? null)
    @php($activeTicket = $customerWorkspace['activeTicket'] ?? null)
    @php($activeOpportunity = $customerWorkspace['activeOpportunity'] ?? null)
    @php($activeQuotation = $customerWorkspace['activeQuotation'] ?? null)
    @php($crmSummary = $workspacePayload['crm']['summary'] ?? [])
    @php($lifecycleSteps = $workspacePayload['crm']['lifecycle'] ?? [])
    @php($actionUrls = $workspacePayload['action_urls'] ?? [])
    @php($latestMessage = $activeMessages->last())
    @php($timelineEvents = collect($customerWorkspace['crm_timeline'] ?? $conversationTimeline)->sortByDesc(fn ($event) => $event['time']?->timestamp ?? 0))
    @php($contactLifecycleLabel = $activeCustomer ? 'Customer' : ($activeLead ? 'Lead / Prospect' : 'Unknown Contact'))
    @php($contactLifecycleClass = $activeCustomer ? 'status-active' : ($activeLead ? 'lead-temperature-warm' : 'status-open'))
    @php($whatsappSessionOpen = $activeConversation?->isWhatsAppSessionOpen() ?? false)
    @php($whatsappSessionWarning = 'Sesi WhatsApp 24 jam sudah berakhir. Gunakan template message untuk menghubungi customer kembali.')
    @php($sendTemplateUrl = route('admin.marketing.whatsapp-templates.index'))

    <section
        class="service-page omnichannel-workspace"
        data-legacy-copy="Inbox percakapan WhatsApp real dari webhook Meta Cloud API."
        data-omni-workspace
        data-poll-url="{{ route('admin.service.omnichannel.poll') }}"
        data-selected-conversation-id="{{ $activeConversation?->id }}"
        data-reverb-enabled="{{ config('broadcasting.default') === 'reverb' && filled(config('broadcasting.connections.reverb.key')) ? 'true' : 'false' }}"
        data-reverb-key="{{ config('broadcasting.connections.reverb.key') }}"
        data-reverb-host="{{ config('broadcasting.connections.reverb.options.host') }}"
        data-reverb-port="{{ config('broadcasting.connections.reverb.options.port') }}"
        data-reverb-scheme="{{ config('broadcasting.connections.reverb.options.scheme') }}"
    >
        <header class="lead-list-header omni-page-heading">
            <div>
                <span class="crm-record-kicker">WHATSAPP CRM</span>
                <h1>WhatsApp Business Workspace</h1>
                <p>Kelola percakapan customer, tindak lanjut lead, dan ticket service dari satu workspace.</p>
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="card customer-alert">{{ session('error') }}</div>
        @endif

        <div class="omni-shell omni-workspace-shell">
            <aside class="card omni-sidebar">
                <form method="GET" action="{{ route('admin.service.omnichannel.index') }}" class="omni-search-form">
                    <label class="field">
                        <span>Search contact</span>
                        <input type="search" name="q" value="{{ $search }}" placeholder="Nama, nomor, atau pesan">
                    </label>
                    <input type="hidden" name="channel" value="{{ $selectedChannel }}">
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                    <div class="omni-filter-tabs">
                        <button name="filter" value="semua" class="{{ $selectedFilter === 'semua' ? 'active' : '' }}">Semua</button>
                        <button name="filter" value="belum-diambil" class="{{ $selectedFilter === 'belum-diambil' ? 'active' : '' }}">Belum Diambil</button>
                        <button name="filter" value="milik-saya" class="{{ $selectedFilter === 'milik-saya' ? 'active' : '' }}">Milik Saya</button>
                        <button name="filter" value="open" class="{{ $selectedFilter === 'open' ? 'active' : '' }}">Open</button>
                        <button name="filter" value="resolved" class="{{ $selectedFilter === 'resolved' ? 'active' : '' }}">Resolved</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.service.omnichannel.bulk-destroy-conversations') }}" class="omni-bulk-form" onsubmit="return confirm('Hapus conversation WhatsApp yang dipilih?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-muted omni-select-mode-toggle" type="button" data-omni-select-mode>Select Conversations</button>
                    <div class="omni-bulk-toolbar">
                        <label><input type="checkbox" data-omni-select-all> Pilih Semua</label>
                        <button class="btn btn-sm omni-bulk-delete" type="submit">Bulk Delete</button>
                    </div>

                    <div class="omni-poll-status" data-omni-poll-status hidden>Memperbarui percakapan...</div>
                    <div class="omni-realtime-status is-fallback" data-omni-realtime-status>
                        <span></span>
                        <strong>Polling fallback</strong>
                    </div>

                    <div class="omni-conversation-list" data-omni-conversation-list>
                        @forelse ($conversations as $conversation)
                            @php($name = $conversation->contact_name ?: $conversation->customer?->name ?: $conversation->lead?->name ?: $conversation->phone_number)
                            @php($initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') ?: 'W')
                            @php($conversationStatus = in_array($conversation->status, ['closed', 'resolved'], true) ? 'Resolved' : 'Open')
                            @php($conversationSessionOpen = $conversation->isWhatsAppSessionOpen())
                            <div class="omni-conversation-row">
                                <label class="omni-select-box" title="Pilih conversation">
                                    <input type="checkbox" name="conversation_ids[]" value="{{ $conversation->id }}">
                                </label>
                                <a
                                    href="{{ route('admin.service.omnichannel.index', ['q' => $search, 'filter' => $selectedFilter, 'status' => $selectedStatus, 'conversation' => $conversation->id]) }}"
                                    class="omni-conversation-item {{ $activeConversation?->id === $conversation->id ? 'active' : '' }}"
                                    data-omni-conversation-link
                                    data-conversation-id="{{ $conversation->id }}"
                                >
                                    <span class="omni-avatar">
                                        {{ strtoupper($initials) }}
                                        <i></i>
                                    </span>
                                    <span class="omni-conversation-main">
                                        <span class="omni-conversation-title">
                                            <strong>{{ $name }}</strong>
                                            <em class="omni-pill {{ strtolower($conversationStatus) }}">{{ $conversationStatus }}</em>
                                        </span>
                                        <small>{{ str($conversation->last_message ?: 'Belum ada pesan')->limit(42) }}</small>
                                        <span class="omni-conversation-badges">
                                            @if ($conversation->assigned_to)
                                                <em class="omni-pill assigned">Assigned</em>
                                            @else
                                                <em class="omni-pill unassigned">Belum Diambil</em>
                                            @endif
                                            <em class="omni-pill {{ $conversationSessionOpen ? 'session-open' : 'session-expired' }}">{{ $conversationSessionOpen ? 'Session Open' : 'Session Expired' }}</em>
                                        </span>
                                    </span>
                                    <span class="omni-conversation-meta">
                                        <time>{{ $conversation->last_message_at?->diffForHumans() ?: '-' }}</time>
                                        @if ($conversation->unread_count > 0)
                                            <b>{{ $conversation->unread_count }}</b>
                                        @endif
                                    </span>
                                </a>
                            </div>
                        @empty
                            <div class="omni-empty-mini">Belum ada percakapan WhatsApp real.</div>
                        @endforelse
                    </div>
                </form>
            </aside>

            <main class="card omni-chat-panel">
                @if ($activeConversation)
                    @php($chatName = $activeConversation->contact_name ?: $activeConversation->customer?->name ?: $activeConversation->lead?->name ?: $activeConversation->phone_number)
                    @php($activeProvider = strtolower((string) ($activeConversation->messages->firstWhere('provider')?->provider ?? 'meta')))
                    @php($activeProviderLabel = $activeProvider === 'meta' ? 'Meta Cloud API' : 'Fonnte')
                    <div class="omni-chat-header" data-omni-chat-header>
                        <div class="omni-chat-title">
                            <span class="omni-avatar compact">{{ strtoupper(mb_substr($chatName, 0, 2)) }}</span>
                            <div>
                                <h2>{{ $chatName }}</h2>
                                <p>{{ $activeConversation->phone_number }} · <span class="omni-provider-badge {{ $activeProvider === 'meta' ? 'meta' : 'fonnte' }}">{{ $activeProviderLabel }}</span></p>
                            </div>
                        </div>
                        @if ($activeConversation->assigned_to)
                            <span class="omni-assigned-note">Sudah diambil oleh {{ $activeConversation->assigned_to }}</span>
                        @else
                            <form method="POST" action="{{ route('admin.service.omnichannel.assign', $activeConversation) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">Ambil</button>
                            </form>
                        @endif
                    </div>

                    <div class="omni-chat-thread" id="omni-chat-thread" data-omni-chat-thread>
                        @php($lastDateLabel = null)
                        @php($activityStripShown = false)
                        @forelse ($activeMessages as $chatMessage)
                            @php($messageTime = $chatMessage->received_at ?? $chatMessage->sent_at ?? $chatMessage->created_at)
                            @php($dateLabel = $messageTime?->isToday() ? 'Hari Ini' : ($messageTime?->isYesterday() ? 'Kemarin' : $messageTime?->format('d M Y')))
                            @if ($dateLabel && $dateLabel !== $lastDateLabel)
                                <div class="omni-date-separator"><span>{{ $dateLabel }}</span></div>
                                @php($lastDateLabel = $dateLabel)
                                @if (! $activityStripShown && $latestMessage)
                                    <div class="omni-activity-strip">
                                        <strong>Last Activity:</strong>
                                        <span>{{ $latestMessage->direction === 'inbound' ? 'Customer replied' : 'Agent replied' }} {{ ($latestMessage->received_at ?? $latestMessage->sent_at ?? $latestMessage->created_at)?->diffForHumans() }}</span>
                                    </div>
                                    @php($activityStripShown = true)
                                @endif
                            @endif
                            <div class="omni-bubble-row {{ $chatMessage->direction === 'outbound' ? 'outbound' : 'inbound' }}">
                                <div class="omni-bubble">
                                    @if ($chatMessage->media_path || $chatMessage->media_url)
                                        @php($mediaUrl = $chatMessage->media_url ?: \Illuminate\Support\Facades\Storage::disk('public')->url($chatMessage->media_path))
                                        @php($mediaName = $chatMessage->media_original_name ?: basename((string) $chatMessage->media_path))
                                        @if (str_starts_with((string) $chatMessage->media_mime, 'image/'))
                                            <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="omni-media-preview">
                                                <img src="{{ $mediaUrl }}" alt="{{ $mediaName }}">
                                            </a>
                                        @elseif (str_starts_with((string) $chatMessage->media_mime, 'video/'))
                                            <video class="omni-media-video" controls preload="metadata">
                                                <source src="{{ $mediaUrl }}" type="{{ $chatMessage->media_mime }}">
                                            </video>
                                        @else
                                            <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="omni-media-file" download>
                                                <span class="omni-media-file-icon">📄</span>
                                                <span class="omni-media-file-main">
                                                    <strong>{{ $mediaName }}</strong>
                                                    <small>{{ $chatMessage->media_size ? number_format($chatMessage->media_size / 1024, 1) . ' KB' : ($chatMessage->media_mime ?: 'attachment') }}</small>
                                                </span>
                                            </a>
                                        @endif
                                    @endif
                                    @if (trim((string) $chatMessage->message) !== '')
                                        <p>{{ $chatMessage->message }}</p>
                                    @endif
                                    <span>{{ $messageTime?->format('H:i') }} · {{ ucfirst($chatMessage->status) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="omni-empty-chat">Belum ada pesan dalam percakapan ini.</div>
                        @endforelse
                    </div>

                    <div class="omni-composer-shell">
                        <div class="omni-emoji-picker" data-omni-emoji-picker hidden>
                            <emoji-picker data-omni-emoji-element></emoji-picker>
                        </div>
                        @if ($activeMessages->count() <= 3)
                            <div class="omni-quick-replies">
                                <button type="button" data-omni-quick-reply="Terima kasih">Terima kasih</button>
                                <button type="button" data-omni-quick-reply="Baik, kami cek terlebih dahulu">Baik, kami cek terlebih dahulu</button>
                                <button type="button" data-omni-quick-reply="Mohon tunggu sebentar">Mohon tunggu sebentar</button>
                                <button type="button" data-omni-quick-reply="Tim kami akan menghubungi Anda">Tim kami akan menghubungi Anda</button>
                            </div>
                        @endif
                        <form class="omni-composer" method="POST" action="{{ route('admin.service.omnichannel.reply', $activeConversation) }}" enctype="multipart/form-data" data-omni-reply-form>
                            @csrf
                            @unless ($whatsappSessionOpen)
                                <div class="omni-session-alert" data-omni-session-alert>
                                    <span>{{ $whatsappSessionWarning }}</span>
                                    <a class="btn btn-sm btn-muted" href="{{ $sendTemplateUrl }}" title="Buka WhatsApp Templates">Send Template</a>
                                </div>
                            @else
                                <div class="omni-session-alert" data-omni-session-alert hidden>
                                    <span>{{ $whatsappSessionWarning }}</span>
                                    <a class="btn btn-sm btn-muted" href="{{ $sendTemplateUrl }}" title="Buka WhatsApp Templates">Send Template</a>
                                </div>
                            @endunless
                            <button type="button" class="omni-icon-btn" title="Emoji" data-omni-emoji-button>☺</button>
                            <button type="button" class="omni-icon-btn" title="Attachment" data-omni-attachment-button>↥</button>
                            <input type="file" name="attachment" data-omni-attachment-input hidden accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.mp4,.mp3">
                            <textarea name="message" rows="2" placeholder="Tulis balasan..." data-omni-message-input @disabled(! $whatsappSessionOpen)></textarea>
                            <span class="omni-attachment-pill" data-omni-attachment-pill hidden>
                                <span class="omni-attachment-name" data-omni-attachment-name></span>
                                <button type="button" class="omni-attachment-clear" title="Hapus attachment" data-omni-attachment-clear>×</button>
                            </span>
                            <button type="submit" class="btn btn-primary" @disabled(! $whatsappSessionOpen)>Send</button>
                        </form>
                    </div>
                @else
                    <div class="omni-chat-header" data-omni-chat-header>
                        <div>
                            <h2>Pilih percakapan</h2>
                            <p>Pesan WhatsApp inbound akan tampil di sini secara realtime-ready.</p>
                        </div>
                    </div>
                    <div class="omni-chat-thread" data-omni-chat-thread>
                        <div class="omni-empty-chat">Belum ada percakapan WhatsApp aktif.</div>
                    </div>
                @endif
            </main>

            <aside class="card omni-profile-panel">
                <div class="omni-workspace-tabs" role="tablist" aria-label="Customer workspace">
                    <button type="button" class="active" role="tab" aria-selected="true" data-omni-profile-tab="contact">Contact</button>
                    <button type="button" role="tab" aria-selected="false" data-omni-profile-tab="crm">CRM</button>
                    @can('omnichannel_notes.view')
                        <button type="button" role="tab" aria-selected="false" data-omni-profile-tab="notes">Notes</button>
                    @endcan
                </div>

                <div role="tabpanel" data-omni-profile-panel="contact" data-omni-contact-panel>
                <div class="omni-profile-head">
                    <span class="omni-avatar large">{{ $activeConversation ? strtoupper(mb_substr($activeConversation->contact_name ?: $activeConversation->phone_number, 0, 2)) : 'WA' }}</span>
                    <h2>{{ $activeConversation?->contact_name ?: $activeCustomer?->name ?: $activeLead?->name ?: 'Customer Workspace' }}</h2>
                    <p>{{ $activeConversation?->phone_number ?: 'Pilih percakapan untuk melihat detail.' }}</p>
                </div>

                <div class="omni-360-card">
                    <h3>CONTACT INFORMATION</h3>
                    <div class="omni-profile-list">
                        <div><strong>Nama</strong><span>{{ $activeConversation?->contact_name ?: $activeCustomer?->name ?: $activeLead?->name ?: '-' }}</span></div>
                        <div><strong>Nomor WhatsApp</strong><span>{{ $activeConversation?->phone_number ?: '-' }}</span></div>
                        <div><strong>Lifecycle</strong><span class="status-badge {{ $contactLifecycleClass }}">{{ $contactLifecycleLabel }}</span></div>
                        <div><strong>Status</strong><span class="status-badge status-{{ $activeConversation?->status ?? 'open' }}">{{ ucfirst($activeConversation?->status ?? 'open') }}</span></div>
                    </div>
                </div>

                @php($hasLead = filled($activeLead))
                @php($hasTicket = filled($activeTicket))
                @php($isClosed = in_array($activeConversation?->status, ['closed', 'resolved'], true))
                @php($currentStage = $isClosed ? 'Resolved' : ($customerWorkspace['lifecycle_step']['label'] ?? ($hasLead ? 'Lead Created' : ($hasTicket ? 'Need Support Ticket' : 'Need Follow Up'))))
                @php($currentStageClass = str($currentStage)->lower()->replace(' ', '-'))

                @php($conversationType = collect((array) ($activeConversation?->tags ?? []))->first() ?: 'general')

                <div class="omni-profile-actions omni-quick-actions">
                    <h3>ACTION</h3>
                    @if ($activeConversation)
                        <form method="POST" action="{{ route('admin.service.omnichannel.classification', $activeConversation) }}" class="omni-type-switcher">
                            @csrf
                            <span class="omni-type-badge">Type: {{ str($conversationType)->headline() }}</span>
                            <select name="conversation_type" aria-label="Change conversation type" onchange="this.form.submit()">
                                <option value="" selected disabled>Change</option>
                                <option value="general" @disabled($conversationType === 'general')>General</option>
                                <option value="sales" @disabled($conversationType === 'sales')>Sales</option>
                                <option value="support" @disabled($conversationType === 'support')>Support</option>
                                <option value="billing" @disabled($conversationType === 'billing')>Billing</option>
                                <option value="project" @disabled($conversationType === 'project')>Project</option>
                            </select>
                        </form>
                    @endif
                    @if ($actionUrls['create_lead'] ?? null)
                        <a class="btn btn-sm btn-primary" href="{{ $actionUrls['create_lead'] }}">Create Lead</a>
                    @endif
                    @if ($actionUrls['open_lead'] ?? null)
                        <a class="btn btn-sm btn-muted omni-action-link" href="{{ $actionUrls['open_lead'] }}">
                            <strong>Open Lead</strong>
                            <span>{{ $activeLead->name }}</span>
                        </a>
                    @endif
                    @if ($actionUrls['create_opportunity'] ?? null)
                        <a class="btn btn-sm btn-primary" href="{{ $actionUrls['create_opportunity'] }}">Create Opportunity</a>
                    @endif
                    @if ($actionUrls['open_opportunity'] ?? null)
                        <a class="btn btn-sm btn-muted omni-action-link" href="{{ $actionUrls['open_opportunity'] }}">
                            <strong>Open Opportunity</strong>
                            <span>{{ $activeOpportunity->title }}</span>
                        </a>
                    @endif
                    @if ($actionUrls['create_quotation'] ?? null)
                        <a class="btn btn-sm btn-primary" href="{{ $actionUrls['create_quotation'] }}">Create Quotation</a>
                    @endif
                    @if ($actionUrls['open_quotation'] ?? null)
                        <a class="btn btn-sm btn-muted omni-action-link" href="{{ $actionUrls['open_quotation'] }}">
                            <strong>Open Quotation</strong>
                            <span>{{ $activeQuotation->quote_number }}</span>
                        </a>
                    @endif
                    @if ($actionUrls['open_deal'] ?? null)
                        <a class="btn btn-sm btn-muted omni-action-link" href="{{ $actionUrls['open_deal'] }}">
                            <strong>Open Deal</strong>
                            <span>{{ $activeQuotation->quote_number }}</span>
                        </a>
                    @endif
                    @if ($actionUrls['create_project'] ?? null)
                        <a class="btn btn-sm btn-primary" href="{{ $actionUrls['create_project'] }}">Create Project</a>
                    @endif
                    @if ($actionUrls['open_project'] ?? null)
                        <a class="btn btn-sm btn-muted omni-action-link" href="{{ $actionUrls['open_project'] }}">
                            <strong>Open Project</strong>
                            <span>{{ $customerWorkspace['activeProject']->project_number }}</span>
                        </a>
                    @endif
                    @if ($actionUrls['create_ticket'] ?? null)
                        <a class="btn btn-sm btn-muted" href="{{ $actionUrls['create_ticket'] }}">Create Ticket</a>
                    @endif
                    @if ($actionUrls['open_ticket'] ?? null)
                        <a class="btn btn-sm btn-muted omni-action-link" href="{{ $actionUrls['open_ticket'] }}">
                            <strong>Open Ticket</strong>
                            <span>{{ $activeTicket->ticket_number }}</span>
                        </a>
                    @endif
                    @if ($actionUrls['open_customer'] ?? null)
                        <a class="btn btn-sm btn-muted omni-action-link" href="{{ $actionUrls['open_customer'] }}">
                            <strong>Open Customer</strong>
                            <span>{{ $activeCustomer->name }}</span>
                        </a>
                    @endif
                </div>
                </div>

                <div role="tabpanel" data-omni-profile-panel="crm" data-omni-crm-panel hidden>
                <div class="omni-360-section omni-current-stage-card">
                    <h3>CURRENT STAGE</h3>
                    <span class="omni-stage-badge {{ $currentStageClass }}">{{ $currentStage }}</span>
                </div>

                <div class="omni-360-section">
                    <h3>Lifecycle Progress</h3>
                    <div class="omni-lifecycle-progress">
                        @foreach ($lifecycleSteps as $step)
                            <span class="{{ $step['active'] ? 'active' : ($step['complete'] ? 'complete' : '') }}">{{ $step['label'] }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="omni-360-section">
                    <h3>CRM Summary</h3>
                    <div class="omni-crm-summary">
                        @foreach (['lead' => 'Lead', 'opportunity' => 'Opportunity', 'quotation' => 'Quotation', 'ticket' => 'Ticket', 'project' => 'Project', 'customer' => 'Customer'] as $key => $label)
                            @php($record = $crmSummary[$key] ?? null)
                            <div class="omni-summary-row">
                                <span>{{ $label }}</span>
                                @if ($record && ($record['url'] ?? null))
                                    <a href="{{ $record['url'] }}">
                                        <strong>{{ $record['label'] }}</strong>
                                        <small>{{ $record['description'] }}</small>
                                    </a>
                                @elseif ($record)
                                    <strong>{{ $record['label'] }}</strong>
                                    <small>{{ $record['description'] }}</small>
                                @else
                                    <em>Not linked</em>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="omni-360-section">
                    <h3>CRM Timeline</h3>
                    <div class="omni-timeline-list">
                    @forelse ($timelineEvents as $event)
                        <article class="omni-timeline-item">
                            <i></i>
                            <div>
                                <strong>{{ $event['label'] }}</strong>
                                <span>{{ $event['description'] }}</span>
                                <small>{{ $event['time']?->format('d M Y H:i') }}</small>
                            </div>
                        </article>
                    @empty
                        <div class="omni-empty-mini">Belum ada event.</div>
                    @endforelse
                    </div>
                </div>

                <div class="omni-360-section">
                    <h3>RECENT CRM DATA</h3>
                    <h4>Recent Ticket</h4>
                    @forelse ($customerWorkspace['tickets'] as $ticket)
                        <a class="omni-crm-link" href="{{ route('admin.service.tickets.show', $ticket) }}">
                            <strong>{{ $ticket->ticket_number }}</strong>
                            <span>{{ str($ticket->subject)->limit(44) }}</span>
                        </a>
                    @empty
                        <div class="omni-empty-mini">Belum ada ticket terkait.</div>
                    @endforelse

                    <h4>Recent Opportunity</h4>
                    @forelse ($customerWorkspace['opportunities'] as $opportunity)
                        <a class="omni-crm-link" href="{{ route('admin.sales.opportunities.show', $opportunity) }}">
                            <strong>{{ $opportunity->title }}</strong>
                            <span>{{ ucfirst($opportunity->status) }} · {{ number_format((float) $opportunity->estimated_value, 0, ',', '.') }}</span>
                        </a>
                    @empty
                        <div class="omni-empty-mini">Belum ada opportunity terkait.</div>
                    @endforelse

                    <h4>Recent Quotation</h4>
                    @forelse ($customerWorkspace['quotations'] as $quotation)
                        <a class="omni-crm-link" href="{{ route('admin.sales.deals.show', $quotation) }}">
                            <strong>{{ $quotation->quote_number }}</strong>
                            <span>{{ str($quotation->title)->limit(44) }} · {{ ucfirst($quotation->status) }}</span>
                        </a>
                    @empty
                        <div class="omni-empty-mini">Belum ada quotation terkait.</div>
                    @endforelse
                </div>

                <div class="omni-profile-actions omni-service-actions">
                    @if ($activeConversation && ! $activeConversation->assigned_to)
                        <form method="POST" action="{{ route('admin.service.omnichannel.assign', $activeConversation) }}">
                            @csrf
                            <button class="btn btn-primary" type="submit">Ambil Conversation</button>
                        </form>
                    @elseif ($activeConversation)
                        <span class="omni-assigned-note">Sudah diambil oleh {{ $activeConversation->assigned_to }}</span>
                    @endif
                    @if ($activeConversation)
                        <form method="POST" action="{{ route('admin.service.omnichannel.resolve', $activeConversation) }}">
                            @csrf
                            <button class="btn btn-muted" type="submit">Mark Closed</button>
                        </form>
                    @endif
                </div>
                </div>

                @can('omnichannel_notes.view')
                    <div
                        class="omni-notes-panel"
                        role="tabpanel"
                        data-omni-profile-panel="notes"
                        @if ($activeConversation)
                            data-notes-url="{{ route('admin.service.omnichannel.notes.index', $activeConversation) }}"
                        @endif
                        hidden
                    >
                        <header class="omni-notes-header">
                            <div>
                                <h3>Internal Notes</h3>
                                <p>Catatan ini hanya terlihat oleh tim internal dan tidak dikirim ke customer.</p>
                            </div>
                            <span>Internal</span>
                        </header>

                        @if ($activeConversation)
                            @can('omnichannel_notes.create')
                                <form
                                    class="omni-notes-form"
                                    action="{{ route('admin.service.omnichannel.notes.store', $activeConversation) }}"
                                    method="POST"
                                    data-omni-notes-form
                                >
                                    @csrf
                                    <label for="omni-internal-note">Catatan internal</label>
                                    <textarea id="omni-internal-note" name="note" maxlength="5000" rows="4" placeholder="Tulis catatan internal..." required></textarea>
                                    <div>
                                        <small><span data-note-character-count>0</span>/5000 karakter</small>
                                        <button type="submit" class="btn btn-primary">Simpan Catatan</button>
                                    </div>
                                </form>
                            @endcan

                            <div class="omni-notes-toast" data-omni-notes-toast hidden></div>
                            <div class="omni-notes-list" data-omni-notes-list>
                                <div class="omni-notes-loading">Memuat catatan internal...</div>
                            </div>
                        @else
                            <div class="omni-notes-empty">Pilih percakapan untuk melihat catatan internal.</div>
                        @endif
                    </div>
                @endcan
            </aside>
        </div>
    </section>

    <script type="module">
        import 'https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js';
    </script>
    <script>
        const omniThread = document.getElementById('omni-chat-thread');
        if (omniThread) {
            omniThread.scrollTop = omniThread.scrollHeight;
        }
        document.querySelector('[data-omni-select-all]')?.addEventListener('change', (event) => {
            document.querySelectorAll('input[name="conversation_ids[]"]').forEach((checkbox) => {
                checkbox.checked = event.target.checked;
            });
        });
        document.querySelector('[data-omni-select-mode]')?.addEventListener('click', (event) => {
            event.preventDefault();
            const bulkForm = event.currentTarget.closest('.omni-bulk-form');
            const isSelecting = bulkForm?.classList.toggle('is-selecting');
            event.currentTarget.textContent = isSelecting ? 'Cancel Selection' : 'Select Conversations';
            if (!isSelecting) {
                document.querySelectorAll('input[name="conversation_ids[]"], [data-omni-select-all]').forEach((checkbox) => {
                    checkbox.checked = false;
                });
            }
        });
        const attachmentButton = document.querySelector('[data-omni-attachment-button]');
        const attachmentInput = document.querySelector('[data-omni-attachment-input]');
        const attachmentPill = document.querySelector('[data-omni-attachment-pill]');
        const attachmentName = document.querySelector('[data-omni-attachment-name]');
        const attachmentClear = document.querySelector('[data-omni-attachment-clear]');
        const emojiButton = document.querySelector('[data-omni-emoji-button]');
        const emojiPicker = document.querySelector('[data-omni-emoji-picker]');
        const messageInput = document.querySelector('[data-omni-message-input]');
        const replyForm = document.querySelector('[data-omni-reply-form]');
        const sessionAlert = document.querySelector('[data-omni-session-alert]');
        const omniWorkspace = document.querySelector('[data-omni-workspace]');
        const pollStatus = document.querySelector('[data-omni-poll-status]');
        const realtimeStatus = document.querySelector('[data-omni-realtime-status]');
        let activeConversationId = omniWorkspace?.dataset.selectedConversationId || '';
        let isPolling = false;
        let realtimeConnectionState = 'fallback';
        const assignButtonLabel = 'Ambil';
        const assignConversationLabel = ['Ambil', 'Conversation'].join(' ');
        const openCustomerLabel = ['Open', 'Customer'].join(' ');
        const openLeadLabel = ['Open', 'Lead'].join(' ');
        const sessionExpiredMessage = 'Sesi WhatsApp 24 jam sudah berakhir. Gunakan template message untuk menghubungi customer kembali.';
        const hasSelectedAttachment = () => (attachmentInput?.files?.length || 0) > 0;
        const isEmojiPickerOpen = () => !!emojiPicker && !emojiPicker.hidden;
        emojiButton?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            emojiPicker.hidden = !emojiPicker.hidden;
        });
        emojiPicker?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
        });
        emojiPicker?.addEventListener('emoji-click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const emoji = event.detail?.unicode;
            if (!emoji || !messageInput) {
                return;
            }

            const start = messageInput.selectionStart ?? messageInput.value.length;
            const end = messageInput.selectionEnd ?? messageInput.value.length;
            messageInput.value = messageInput.value.slice(0, start) + emoji + messageInput.value.slice(end);
            const nextCursor = start + emoji.length;
            messageInput.focus();
            messageInput.setSelectionRange(nextCursor, nextCursor);
        });
        document.addEventListener('click', (event) => {
            if (!emojiPicker || emojiPicker.hidden) {
                return;
            }

            if (!emojiPicker.contains(event.target) && !emojiButton?.contains(event.target)) {
                emojiPicker.hidden = true;
            }
        });
        attachmentButton?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            attachmentInput?.click();
        });
        attachmentInput?.addEventListener('change', () => {
            const fileName = attachmentInput.files?.[0]?.name || '';
            attachmentName.textContent = fileName;
            attachmentPill.hidden = fileName === '';
        });
        attachmentClear?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            attachmentInput.value = '';
            attachmentName.textContent = '';
            attachmentPill.hidden = true;
        });
        document.querySelectorAll('[data-omni-quick-reply]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!messageInput) {
                    return;
                }

                messageInput.value = button.dataset.omniQuickReply || '';
                messageInput.focus();
                messageInput.setSelectionRange(messageInput.value.length, messageInput.value.length);
            });
        });

        const profileTabs = Array.from(document.querySelectorAll('[data-omni-profile-tab]'));
        const profilePanels = Array.from(document.querySelectorAll('[data-omni-profile-panel]'));
        const notesPanel = document.querySelector('[data-omni-profile-panel="notes"]');
        const notesList = notesPanel?.querySelector('[data-omni-notes-list]');
        const notesForm = notesPanel?.querySelector('[data-omni-notes-form]');
        const notesTextarea = notesForm?.querySelector('textarea[name="note"]');
        const noteCharacterCount = notesForm?.querySelector('[data-note-character-count]');
        const notesToast = notesPanel?.querySelector('[data-omni-notes-toast]');
        const profileTabStorageKey = 'krakatau.omnichannel.profileTab';
        let notesLoaded = false;

        const showNotesToast = (message, isError = false) => {
            if (!notesToast) return;

            notesToast.textContent = message;
            notesToast.classList.toggle('is-error', isError);
            notesToast.hidden = false;
            window.setTimeout(() => {
                notesToast.hidden = true;
            }, 3000);
        };

        const renderNotes = (notes) => {
            if (!notesList) return;

            notesList.replaceChildren();

            if (!notes.length) {
                const empty = document.createElement('div');
                empty.className = 'omni-notes-empty';
                empty.textContent = 'Belum ada catatan internal.';
                notesList.append(empty);
                return;
            }

            notes.forEach((note) => {
                const item = document.createElement('article');
                item.className = 'omni-note-item';

                const meta = document.createElement('div');
                const author = document.createElement('strong');
                const date = document.createElement('time');
                const content = document.createElement('p');
                author.textContent = note.user?.name || 'User CRM';
                date.textContent = note.created_at
                    ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(note.created_at))
                    : '-';
                content.textContent = note.note;
                meta.append(author, date);
                item.append(meta, content);
                notesList.append(item);
            });
        };

        const loadNotes = async () => {
            if (!notesPanel?.dataset.notesUrl || !notesList) return;

            try {
                const response = await fetch(notesPanel.dataset.notesUrl, {
                    headers: { 'Accept': 'application/json' },
                });
                const payload = await response.json();

                if (!response.ok) throw new Error(payload.message || 'Catatan internal gagal dimuat.');

                renderNotes(payload.data || []);
                notesLoaded = true;
            } catch (error) {
                console.error('Failed to load internal notes:', error);
                notesList.innerHTML = '<div class="omni-notes-empty is-error">Catatan internal gagal dimuat.</div>';
                showNotesToast(error.message || 'Catatan internal gagal dimuat.', true);
            }
        };

        const availableProfileTabs = profileTabs.map((tab) => tab.dataset.omniProfileTab);

        const storedProfileTab = () => {
            try {
                return window.localStorage.getItem(profileTabStorageKey);
            } catch (error) {
                console.warn('Unable to read Omnichannel tab state:', error);
                return null;
            }
        };

        const persistProfileTab = (selectedTab) => {
            try {
                window.localStorage.setItem(profileTabStorageKey, selectedTab);
            } catch (error) {
                console.warn('Unable to persist Omnichannel tab state:', error);
            }

            const nextUrl = `${window.location.pathname}${window.location.search}#${selectedTab}`;
            window.history.replaceState(null, '', nextUrl);
        };

        const activateProfileTab = (requestedTab, persist = true) => {
            const selectedTab = availableProfileTabs.includes(requestedTab) ? requestedTab : 'contact';

            profileTabs.forEach((tab) => {
                const isActive = tab.dataset.omniProfileTab === selectedTab;
                tab.classList.toggle('active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            profilePanels.forEach((panel) => {
                panel.hidden = panel.dataset.omniProfilePanel !== selectedTab;
            });

            if (persist) persistProfileTab(selectedTab);
            if (selectedTab === 'notes' && !notesLoaded) loadNotes();
        };

        profileTabs.forEach((tab) => {
            tab.addEventListener('click', () => activateProfileTab(tab.dataset.omniProfileTab));
        });

        const hashProfileTab = window.location.hash.slice(1).toLowerCase();
        const initialProfileTab = availableProfileTabs.includes(hashProfileTab)
            ? hashProfileTab
            : storedProfileTab();
        activateProfileTab(initialProfileTab || 'contact');

        window.addEventListener('hashchange', () => {
            activateProfileTab(window.location.hash.slice(1).toLowerCase());
        });

        notesTextarea?.addEventListener('input', () => {
            if (noteCharacterCount) noteCharacterCount.textContent = notesTextarea.value.length;
        });

        notesForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitButton = notesForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            try {
                const response = await fetch(notesForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: new FormData(notesForm),
                });
                const payload = await response.json();

                if (!response.ok) {
                    const validationMessage = Object.values(payload.errors || {})[0]?.[0];
                    throw new Error(validationMessage || payload.message || 'Catatan internal gagal disimpan.');
                }

                notesForm.reset();
                if (noteCharacterCount) noteCharacterCount.textContent = '0';
                showNotesToast(payload.message || 'Catatan internal berhasil disimpan.');
                await loadNotes();
                await pollOmnichannel({ silent: true });
            } catch (error) {
                console.error('Failed to save internal note:', error);
                showNotesToast(error.message || 'Catatan internal gagal disimpan.', true);
            } finally {
                submitButton.disabled = false;
            }
        });

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const conversationTypeLabel = (type) => {
            const labels = {
                general: 'General',
                sales: 'Sales',
                support: 'Support',
                billing: 'Billing',
                project: 'Project',
            };

            return labels[type] || 'General';
        };

        const renderConversationTypeSwitcher = (contact) => {
            if (!contact?.classification_url) return '';

            const currentType = contact.conversation_type || 'general';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const options = ['general', 'sales', 'support', 'billing', 'project']
                .map((type) => `<option value="${type}" ${type === currentType ? 'disabled' : ''}>${conversationTypeLabel(type)}</option>`)
                .join('');

            return `
                <form method="POST" action="${contact.classification_url}" class="omni-type-switcher">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <span class="omni-type-badge">Type: ${escapeHtml(conversationTypeLabel(currentType))}</span>
                    <select name="conversation_type" aria-label="Change conversation type" onchange="this.form.submit()">
                        <option value="" selected disabled>Change</option>
                        ${options}
                    </select>
                </form>
            `;
        };

        const renderConversationActions = (contact) => {
            const actions = contact?.actions || {};
            const buttons = [];

            if (actions.create_lead) buttons.push(`<a class="btn btn-sm btn-primary" href="${actions.create_lead}">Create Lead</a>`);
            if (actions.open_lead) buttons.push(`<a class="btn btn-sm btn-muted omni-action-link" href="${actions.open_lead}"><strong>${openLeadLabel}</strong><span>${escapeHtml(contact.lead_name)}</span></a>`);
            if (actions.create_opportunity) buttons.push(`<a class="btn btn-sm btn-primary" href="${actions.create_opportunity}">Create Opportunity</a>`);
            if (actions.open_opportunity) buttons.push(`<a class="btn btn-sm btn-muted omni-action-link" href="${actions.open_opportunity}"><strong>Open Opportunity</strong><span>${escapeHtml(contact?.opportunity_name || '')}</span></a>`);
            if (actions.create_quotation) buttons.push(`<a class="btn btn-sm btn-primary" href="${actions.create_quotation}">Create Quotation</a>`);
            if (actions.open_quotation) buttons.push(`<a class="btn btn-sm btn-muted omni-action-link" href="${actions.open_quotation}"><strong>Open Quotation</strong><span>${escapeHtml(contact?.quotation_label || '')}</span></a>`);
            if (actions.open_deal) buttons.push(`<a class="btn btn-sm btn-muted omni-action-link" href="${actions.open_deal}"><strong>Open Deal</strong><span>${escapeHtml(contact?.quotation_label || '')}</span></a>`);
            if (actions.create_project) buttons.push(`<a class="btn btn-sm btn-primary" href="${actions.create_project}">Create Project</a>`);
            if (actions.open_project) buttons.push(`<a class="btn btn-sm btn-muted omni-action-link" href="${actions.open_project}"><strong>Open Project</strong><span>${escapeHtml(contact?.project_label || '')}</span></a>`);
            if (actions.create_ticket) buttons.push(`<a class="btn btn-sm btn-muted" href="${actions.create_ticket}">Create Ticket</a>`);
            if (actions.open_ticket) buttons.push(`<a class="btn btn-sm btn-muted omni-action-link" href="${actions.open_ticket}"><strong>Open Ticket</strong><span>${escapeHtml(contact?.ticket_label || '')}</span></a>`);
            if (actions.open_customer) buttons.push(`<a class="btn btn-sm btn-muted omni-action-link" href="${actions.open_customer}"><strong>${openCustomerLabel}</strong><span>${escapeHtml(contact.customer_name)}</span></a>`);

            return buttons.join('') || '<div class="omni-empty-mini">Belum ada action tersedia.</div>';
        };

        const updatePollStatus = (isActive) => {
            if (!pollStatus) return;
            pollStatus.hidden = !isActive;
        };

        const updateRealtimeStatus = (status) => {
            if (!realtimeStatus) return;

            const labels = {
                connected: 'Realtime connected',
                reconnecting: 'Reconnecting',
                fallback: 'Polling fallback',
            };

            realtimeConnectionState = ['connected', 'reconnecting', 'fallback'].includes(status) ? status : 'fallback';
            realtimeStatus.classList.toggle('is-connected', realtimeConnectionState === 'connected');
            realtimeStatus.classList.toggle('is-reconnecting', realtimeConnectionState === 'reconnecting');
            realtimeStatus.classList.toggle('is-fallback', realtimeConnectionState === 'fallback');
            const label = realtimeStatus.querySelector('strong');
            if (label) label.textContent = labels[realtimeConnectionState];
        };

        const currentPollUrl = () => {
            if (!omniWorkspace?.dataset.pollUrl) return null;

            const url = new URL(omniWorkspace.dataset.pollUrl, window.location.origin);
            const currentParams = new URLSearchParams(window.location.search);
            ['q', 'filter', 'status', 'channel'].forEach((key) => {
                const value = currentParams.get(key);
                if (value) url.searchParams.set(key, value);
            });
            if (activeConversationId) url.searchParams.set('conversation', activeConversationId);

            return url;
        };

        const updateBrowserConversation = (conversationId) => {
            const url = new URL(window.location.href);
            if (conversationId) {
                url.searchParams.set('conversation', conversationId);
            } else {
                url.searchParams.delete('conversation');
            }
            if (!url.hash) {
                const activeTab = document.querySelector('[data-omni-profile-tab].active')?.dataset.omniProfileTab || 'contact';
                url.hash = activeTab;
            }
            window.history.replaceState(null, '', url);
        };

        const renderConversationList = (conversations) => {
            const list = document.querySelector('[data-omni-conversation-list]');
            if (!list) return;

            if (!conversations.length) {
                list.innerHTML = '<div class="omni-empty-mini">Belum ada percakapan WhatsApp real.</div>';
                return;
            }

            list.innerHTML = conversations.map((conversation) => `
                <div class="omni-conversation-row">
                    <label class="omni-select-box" title="Pilih conversation">
                        <input type="checkbox" name="conversation_ids[]" value="${conversation.id}">
                    </label>
                    <a href="${conversation.href}" class="omni-conversation-item ${conversation.is_active ? 'active' : ''}" data-omni-conversation-link data-conversation-id="${conversation.id}">
                        <span class="omni-avatar">${escapeHtml(String(conversation.initials).toUpperCase())}<i></i></span>
                        <span class="omni-conversation-main">
                            <span class="omni-conversation-title">
                                <strong>${escapeHtml(conversation.name)}</strong>
                                <em class="omni-pill ${escapeHtml(conversation.status_class)}">${escapeHtml(conversation.status_label)}</em>
                            </span>
                            <small>${escapeHtml(conversation.last_message)}</small>
                            <span class="omni-conversation-badges">
                                ${conversation.assigned ? '<em class="omni-pill assigned">Assigned</em>' : '<em class="omni-pill unassigned">Belum Diambil</em>'}
                                <em class="omni-pill ${escapeHtml(conversation.session_class || 'session-expired')}">${escapeHtml(conversation.session_label || 'Session Expired')}</em>
                            </span>
                        </span>
                        <span class="omni-conversation-meta">
                            <time>${escapeHtml(conversation.last_message_at)}</time>
                            ${conversation.unread_count > 0 ? `<b>${conversation.unread_count}</b>` : ''}
                        </span>
                    </a>
                </div>
            `).join('');
        };

        const mediaMarkup = (media) => {
            if (!media) return '';

            if (media.is_image) {
                return `<a href="${media.url}" target="_blank" rel="noopener" class="omni-media-preview"><img src="${media.url}" alt="${escapeHtml(media.name)}"></a>`;
            }

            if (media.is_video) {
                return `<video class="omni-media-video" controls preload="metadata"><source src="${media.url}" type="${escapeHtml(media.mime)}"></video>`;
            }

            return `
                <a href="${media.url}" target="_blank" rel="noopener" class="omni-media-file" download>
                    <span class="omni-media-file-icon">📄</span>
                    <span class="omni-media-file-main">
                        <strong>${escapeHtml(media.name)}</strong>
                        <small>${escapeHtml(media.size_label)}</small>
                    </span>
                </a>
            `;
        };

        const renderThread = (messages) => {
            const thread = document.querySelector('[data-omni-chat-thread]');
            if (!thread) return;

            const isTyping = document.activeElement === messageInput && (messageInput?.value || '').trim() !== '';
            if (isTyping) return;

            const previousDistanceFromBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight;
            const shouldStickToBottom = previousDistanceFromBottom < 80;
            let lastDateLabel = null;
            let activityStripShown = false;
            const latestMessage = messages[messages.length - 1];

            if (!messages.length) {
                thread.innerHTML = '<div class="omni-empty-chat">Belum ada pesan dalam percakapan ini.</div>';
                return;
            }

            thread.innerHTML = messages.map((message) => {
                const dateSeparator = message.date_label && message.date_label !== lastDateLabel
                    ? (() => {
                        lastDateLabel = message.date_label;
                        const activityStrip = !activityStripShown && latestMessage
                            ? (() => {
                                activityStripShown = true;
                                return `<div class="omni-activity-strip"><strong>Last Activity:</strong><span>${escapeHtml(latestMessage.activity_label)} ${escapeHtml(latestMessage.activity_time)}</span></div>`;
                            })()
                            : '';
                        return `<div class="omni-date-separator"><span>${escapeHtml(message.date_label)}</span></div>${activityStrip}`;
                    })()
                    : '';

                return `
                    ${dateSeparator}
                    <div class="omni-bubble-row ${message.direction === 'outbound' ? 'outbound' : 'inbound'}">
                        <div class="omni-bubble">
                            ${mediaMarkup(message.media)}
                            ${message.message.trim() !== '' ? `<p>${escapeHtml(message.message)}</p>` : ''}
                            <span>${escapeHtml(message.time)} · ${escapeHtml(message.status)}</span>
                        </div>
                    </div>
                `;
            }).join('');

            if (shouldStickToBottom) {
                thread.scrollTop = thread.scrollHeight;
            } else {
                thread.scrollTop = Math.max(0, thread.scrollHeight - thread.clientHeight - previousDistanceFromBottom);
            }
        };

        const renderChatHeader = (conversation) => {
            const header = document.querySelector('[data-omni-chat-header]');
            if (!header) return;

            if (!conversation) {
                header.innerHTML = '<div><h2>Pilih percakapan</h2><p>Pesan WhatsApp inbound akan tampil di sini secara realtime-ready.</p></div>';
                updateComposerSession(null);
                return;
            }

            header.innerHTML = `
                <div class="omni-chat-title">
                    <span class="omni-avatar compact">${escapeHtml(conversation.initials)}</span>
                    <div>
                        <h2>${escapeHtml(conversation.name)}</h2>
                        <p>${escapeHtml(conversation.phone_number)} · <span class="omni-provider-badge ${escapeHtml(conversation.provider_class)}">${escapeHtml(conversation.provider_label)}</span></p>
                    </div>
                </div>
                ${conversation.assigned_to
                    ? `<span class="omni-assigned-note">Sudah diambil oleh ${escapeHtml(conversation.assigned_to)}</span>`
                    : `<form method="POST" action="${conversation.assign_url}"><input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}"><button class="btn btn-primary" type="submit">${assignButtonLabel}</button></form>`}
            `;

            if (replyForm && conversation.reply_url) {
                replyForm.action = conversation.reply_url;
            }
            updateComposerSession(conversation);
            if (notesPanel) {
                if (conversation.notes_url) {
                    notesPanel.dataset.notesUrl = conversation.notes_url;
                } else {
                    delete notesPanel.dataset.notesUrl;
                }
            }
            if (notesForm && conversation.notes_store_url) {
                notesForm.action = conversation.notes_store_url;
            }
        };

        const updateComposerSession = (conversation) => {
            const isOpen = !!conversation?.is_whatsapp_session_open;
            const submitButton = replyForm?.querySelector('button[type="submit"]');

            if (messageInput) {
                messageInput.disabled = !isOpen;
                messageInput.placeholder = isOpen ? 'Tulis balasan...' : sessionExpiredMessage;
            }
            if (submitButton) {
                submitButton.disabled = !isOpen;
            }
            if (attachmentButton) {
                attachmentButton.disabled = !isOpen;
                attachmentButton.title = isOpen ? 'Attachment' : sessionExpiredMessage;
            }
            if (emojiButton) {
                emojiButton.disabled = !isOpen;
                emojiButton.title = isOpen ? 'Emoji' : sessionExpiredMessage;
            }
            if (sessionAlert) {
                sessionAlert.hidden = isOpen;
                const message = sessionAlert.querySelector('span');
                if (message) message.textContent = conversation?.session_warning || sessionExpiredMessage;
            }
        };

        const renderWorkspacePanels = (workspace) => {
            const contact = workspace?.contact;
            const crm = workspace?.crm;
            const contactPanel = document.querySelector('[data-omni-contact-panel]');
            const crmPanel = document.querySelector('[data-omni-crm-panel]');

            if (contactPanel && contact) {
                contactPanel.innerHTML = `
                    <div class="omni-profile-head">
                        <span class="omni-avatar large">${escapeHtml(contact.initials)}</span>
                        <h2>${escapeHtml(contact.name)}</h2>
                        <p>${escapeHtml(contact.phone_number)}</p>
                    </div>
                    <div class="omni-360-card">
                        <h3>CONTACT INFORMATION</h3>
                        <div class="omni-profile-list">
                            <div><strong>Nama</strong><span>${escapeHtml(contact.name)}</span></div>
                            <div><strong>Nomor WhatsApp</strong><span>${escapeHtml(contact.phone_number)}</span></div>
                            <div><strong>Lifecycle</strong><span class="status-badge ${escapeHtml(contact.lifecycle_class)}">${escapeHtml(contact.lifecycle_label)}</span></div>
                            <div><strong>Status</strong><span class="status-badge ${escapeHtml(contact.status_class)}">${escapeHtml(contact.status)}</span></div>
                        </div>
                    </div>
                    <div class="omni-profile-actions omni-quick-actions">
                        <h3>ACTION</h3>
                        ${renderConversationTypeSwitcher(contact)}
                        ${renderConversationActions(contact)}
                    </div>
                `;
            }

            if (crmPanel && crm) {
                const renderLinks = (items, emptyText) => items?.length
                    ? items.map((item) => `<a class="omni-crm-link" href="${item.url}"><strong>${escapeHtml(item.label)}</strong><span>${escapeHtml(item.description)}</span></a>`).join('')
                    : `<div class="omni-empty-mini">${emptyText}</div>`;
                const renderLifecycle = (steps) => (steps || []).map((step) => `
                    <span class="${step.active ? 'active' : (step.complete ? 'complete' : '')}">${escapeHtml(step.label)}</span>
                `).join('');
                const renderSummaryRow = (key, label) => {
                    const record = crm.summary?.[key];

                    if (!record) {
                        return `<div class="omni-summary-row"><span>${label}</span><em>Not linked</em></div>`;
                    }

                    const content = `<strong>${escapeHtml(record.label)}</strong><small>${escapeHtml(record.description)}</small>`;

                    return `
                        <div class="omni-summary-row">
                            <span>${label}</span>
                            ${record.url ? `<a href="${record.url}">${content}</a>` : content}
                        </div>
                    `;
                };

                crmPanel.innerHTML = `
                    <div class="omni-360-section omni-current-stage-card">
                        <h3>CURRENT STAGE</h3>
                        <span class="omni-stage-badge ${escapeHtml(crm.current_stage_class)}">${escapeHtml(crm.current_stage)}</span>
                    </div>
                    <div class="omni-360-section">
                        <h3>Lifecycle Progress</h3>
                        <div class="omni-lifecycle-progress">${renderLifecycle(crm.lifecycle)}</div>
                    </div>
                    <div class="omni-360-section">
                        <h3>CRM Summary</h3>
                        <div class="omni-crm-summary">
                            ${renderSummaryRow('lead', 'Lead')}
                            ${renderSummaryRow('opportunity', 'Opportunity')}
                            ${renderSummaryRow('quotation', 'Quotation')}
                            ${renderSummaryRow('ticket', 'Ticket')}
                            ${renderSummaryRow('project', 'Project')}
                            ${renderSummaryRow('customer', 'Customer')}
                        </div>
                    </div>
                    <div class="omni-360-section">
                        <h3>CRM Timeline</h3>
                        <div class="omni-timeline-list">
                            ${crm.events?.length ? crm.events.map((event) => `
                                <article class="omni-timeline-item">
                                    <i></i>
                                    <div>
                                        <strong>${escapeHtml(event.label)}</strong>
                                        <span>${escapeHtml(event.description)}</span>
                                        <small>${escapeHtml(event.time)}</small>
                                    </div>
                                </article>
                            `).join('') : '<div class="omni-empty-mini">Belum ada event.</div>'}
                        </div>
                    </div>
                    <div class="omni-360-section">
                        <h3>RECENT CRM DATA</h3>
                        <h4>Recent Ticket</h4>
                        ${renderLinks(crm.tickets || [], 'Belum ada ticket terkait.')}
                        <h4>Recent Opportunity</h4>
                        ${renderLinks(crm.opportunities || [], 'Belum ada opportunity terkait.')}
                        <h4>Recent Quotation</h4>
                        ${renderLinks(crm.quotations || [], 'Belum ada quotation terkait.')}
                    </div>
                    <div class="omni-profile-actions omni-service-actions">
                        ${crm.assigned_to
                            ? `<span class="omni-assigned-note">Sudah diambil oleh ${escapeHtml(crm.assigned_to)}</span>`
                            : `<form method="POST" action="${crm.assign_url}"><input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}"><button class="btn btn-primary" type="submit">${assignConversationLabel}</button></form>`}
                        ${crm.resolve_url ? `<form method="POST" action="${crm.resolve_url}"><input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}"><button class="btn btn-muted" type="submit">Mark Closed</button></form>` : ''}
                    </div>
                `;
            }
        };

        const applyPollPayload = (payload) => {
            const data = payload?.data || {};
            activeConversationId = data.selected_conversation_id ? String(data.selected_conversation_id) : activeConversationId;
            if (omniWorkspace) omniWorkspace.dataset.selectedConversationId = activeConversationId;
            renderConversationList(data.conversations || []);
            renderChatHeader(data.selected_conversation || null);
            renderThread(data.messages || []);
            renderWorkspacePanels(data.workspace || {});
            if (document.querySelector('[data-omni-profile-tab="notes"].active')) {
                notesLoaded = false;
                loadNotes();
            }
        };

        const pollOmnichannel = async ({ silent = false, source = 'polling' } = {}) => {
            const url = currentPollUrl();
            if (!url || isPolling) return;

            isPolling = true;
            if (!silent) updatePollStatus(true);

            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Omnichannel polling gagal.');
                applyPollPayload(payload);
            } catch (error) {
                console.error('Failed to poll omnichannel inbox:', error);
                if (source !== 'reverb') updateRealtimeStatus('fallback');
            } finally {
                isPolling = false;
                updatePollStatus(false);
            }
        };

        document.addEventListener('click', async (event) => {
            const link = event.target.closest('[data-omni-conversation-link]');
            if (!link) return;

            event.preventDefault();
            activeConversationId = link.dataset.conversationId || '';
            updateBrowserConversation(activeConversationId);
            notesLoaded = false;
            await pollOmnichannel({ silent: false });
        });

        replyForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitButton = replyForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            try {
                const response = await fetch(replyForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: new FormData(replyForm),
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Balasan gagal dikirim.');

                replyForm.reset();
                if (attachmentName) attachmentName.textContent = '';
                if (attachmentPill) attachmentPill.hidden = true;
                await pollOmnichannel({ silent: true });
            } catch (error) {
                console.error('Failed to send WhatsApp reply:', error);
                if ((error.message || '').includes(sessionExpiredMessage)) {
                    updateComposerSession({
                        is_whatsapp_session_open: false,
                        session_warning: sessionExpiredMessage,
                    });
                }
                showNotesToast(error.message || 'Balasan gagal dikirim.', true);
            } finally {
                submitButton.disabled = messageInput?.disabled ?? false;
            }
        });

        updateRealtimeStatus('fallback');

        window.krakatauOmnichannelRealtime = {
            activeConversationId: () => activeConversationId,
            setStatus: updateRealtimeStatus,
            refresh: async (event = {}) => {
                if (event.type === 'ConversationNoteCreated' && document.querySelector('[data-omni-profile-tab="notes"].active')) {
                    notesLoaded = false;
                }
                await pollOmnichannel({ silent: true, source: 'reverb' });
            },
        };

        window.setInterval(() => {
            if (realtimeConnectionState !== 'connected') {
                pollOmnichannel({ silent: true, source: 'polling' });
            }
        }, 5000);
    </script>

    @php($omnichannelViteManifest = file_exists(public_path('build/manifest.json')) ? json_decode(file_get_contents(public_path('build/manifest.json')), true) : [])
    @if (file_exists(public_path('hot')) || isset($omnichannelViteManifest['resources/js/omnichannel-realtime.js']))
        @vite('resources/js/omnichannel-realtime.js')
    @endif

    <style>
        .omni-bulk-form{display:grid;grid-template-rows:auto minmax(0,1fr);gap:.6rem;min-height:0}
        .omni-bulk-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.65rem;color:#6f6b7d;font-size:.76rem;font-weight:800}
        .omni-bulk-toolbar label{display:inline-flex;align-items:center;gap:.35rem}
        .omni-conversation-row{display:grid;grid-template-columns:auto minmax(0,1fr);align-items:stretch;gap:.45rem}
        .omni-select-box{display:grid;place-items:center;min-width:1.6rem}
        .omni-provider-badge{display:inline-flex;align-items:center;justify-content:center;width:max-content;border-radius:999px;padding:.18rem .5rem;font-size:.68rem;font-style:normal;font-weight:900;line-height:1;white-space:nowrap}
        .omni-provider-badge.meta{background:#eef6ff;color:#1677c6}
        .omni-provider-badge.fonnte{background:#e8f8ef;color:#168a49}
        .omni-assigned-note{display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(24,39,75,.12);border-radius:.5rem;padding:.62rem .8rem;background:#f8f8fb;color:#5d596c;font-size:.78rem;font-weight:900}
        .omni-composer{grid-template-columns:auto auto minmax(0,1fr) minmax(0,9rem) auto}
        .omni-emoji-picker{width:min(350px,calc(100% - 2rem));height:420px;max-height:50vh;margin:.75rem 1rem 0;border:1px solid rgba(24,39,75,.12);border-radius:.5rem;background:#fff;box-shadow:0 10px 24px rgba(24,39,75,.12);overflow:hidden}
        .omni-emoji-picker[hidden]{display:none}
        .omni-emoji-picker emoji-picker{width:100%;height:100%;--background:#fff;--border-color:rgba(24,39,75,.12);--button-hover-background:#eef6ff;--button-active-background:#e7f1ff;--indicator-color:#7367f0;--input-border-color:#dbdade;--input-font-color:#5d596c;--input-placeholder-color:#a5a3ae;--outline-color:#7367f0;--category-emoji-size:1.2rem;--emoji-size:1.35rem}
        @media (prefers-color-scheme: dark){.omni-emoji-picker{background:#2f3349;border-color:rgba(255,255,255,.14);box-shadow:0 10px 24px rgba(0,0,0,.35)}.omni-emoji-picker emoji-picker{--background:#2f3349;--border-color:rgba(255,255,255,.14);--button-hover-background:#3b405a;--button-active-background:#454b68;--input-border-color:#565b75;--input-font-color:#f5f5f7;--input-placeholder-color:#b6bdd1;--outline-color:#7367f0}}
        [data-theme="dark"] .omni-emoji-picker,.dark .omni-emoji-picker{background:#2f3349;border-color:rgba(255,255,255,.14);box-shadow:0 10px 24px rgba(0,0,0,.35)}
        [data-theme="dark"] .omni-emoji-picker emoji-picker,.dark .omni-emoji-picker emoji-picker{--background:#2f3349;--border-color:rgba(255,255,255,.14);--button-hover-background:#3b405a;--button-active-background:#454b68;--input-border-color:#565b75;--input-font-color:#f5f5f7;--input-placeholder-color:#b6bdd1;--outline-color:#7367f0}
        .omni-bubble{max-width:min(320px,78%);padding:8px 10px;overflow:hidden}
        .omni-media-preview{display:block;margin-bottom:.45rem}
        .omni-media-preview img{display:block;width:auto;max-width:min(260px,100%);max-height:180px;border-radius:.5rem;object-fit:cover}
        .omni-media-video{display:block;width:100%;max-width:260px;max-height:160px;margin-bottom:.45rem;border-radius:.5rem;object-fit:cover;background:#111}
        .omni-media-file{display:grid;grid-template-columns:2.25rem minmax(0,1fr);align-items:center;gap:.55rem;width:min(260px,100%);min-height:60px;max-height:80px;margin-bottom:.45rem;padding:.5rem .6rem;border:1px solid rgba(24,39,75,.12);border-radius:.5rem;background:rgba(255,255,255,.72);color:inherit;text-decoration:none;overflow:hidden}
        .omni-media-file-icon{display:grid;place-items:center;width:2.25rem;height:2.25rem;border-radius:.45rem;background:#eef6ff;color:#1677c6;font-size:1.1rem}
        .omni-media-file-main{display:grid;gap:.12rem;min-width:0;overflow:hidden}
        .omni-media-file strong{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.82rem;line-height:1.2}
        .omni-media-file small{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#6f6b7d;font-size:.7rem}
        .omni-bubble-row.outbound .omni-media-file{background:rgba(255,255,255,.16);border-color:rgba(255,255,255,.26)}
        .omni-bubble-row.outbound .omni-media-file small{color:rgba(255,255,255,.72)}
        .omni-lifecycle-progress{display:flex;flex-wrap:wrap;gap:.4rem}
        .omni-lifecycle-progress span{display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(24,39,75,.12);border-radius:999px;padding:.28rem .55rem;background:#fff;color:#6f6b7d;font-size:.7rem;font-weight:900;white-space:nowrap}
        .omni-lifecycle-progress span.complete{background:#e8f8ef;color:#168a49;border-color:rgba(22,138,73,.18)}
        .omni-lifecycle-progress span.active{background:#7367f0;color:#fff;border-color:#7367f0}
        .omni-crm-summary{display:grid;gap:.55rem}
        .omni-summary-row{display:grid;grid-template-columns:7rem minmax(0,1fr);align-items:center;gap:.55rem;border-bottom:1px solid rgba(24,39,75,.08);padding-bottom:.5rem}
        .omni-summary-row:last-child{border-bottom:0;padding-bottom:0}
        .omni-summary-row>span{color:#6f6b7d;font-size:.72rem;font-weight:900;text-transform:uppercase}
        .omni-summary-row a,.omni-summary-row strong{min-width:0;color:#5d596c;text-decoration:none;font-size:.78rem;font-weight:900}
        .omni-summary-row a{display:grid;gap:.1rem}
        .omni-summary-row small,.omni-summary-row em{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#a5a3ae;font-size:.72rem;font-style:normal;font-weight:800}
        .omni-attachment-pill{display:inline-flex;align-items:center;gap:.35rem;min-width:0;max-width:9rem;border:1px solid rgba(24,39,75,.12);border-radius:.5rem;padding:.35rem .45rem;background:#f8f8fb;color:#6f6b7d;font-size:.75rem;font-weight:800}
        .omni-attachment-pill[hidden]{display:none}
        .omni-attachment-name{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .omni-attachment-clear{display:grid;place-items:center;width:1.15rem;height:1.15rem;border:0;border-radius:999px;background:#e7e5ef;color:#5d596c;cursor:pointer;font-weight:900;line-height:1}
        .omni-pill.session-open{background:#e8f8ef;color:#168a49}
        .omni-pill.session-expired{background:#fff4de;color:#a35a00}
        .omni-session-alert{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;gap:.75rem;border:1px solid rgba(255,159,67,.28);border-radius:.65rem;padding:.7rem .85rem;background:#fff8e8;color:#8a4b00;font-size:.82rem;font-weight:800;line-height:1.45}
        .omni-session-alert[hidden]{display:none}
        .omni-session-alert .btn{white-space:nowrap}
        .omni-composer textarea:disabled{background:#f8f8fb;color:#a5a3ae;cursor:not-allowed}
        .omni-icon-btn:disabled{opacity:.45;cursor:not-allowed}
        @media (max-width:720px){.omni-session-alert{align-items:stretch;flex-direction:column}.omni-session-alert .btn{width:100%}}
    </style>
@endsection
