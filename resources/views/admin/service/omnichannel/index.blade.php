@extends('admin.layouts.app')

@section('title', 'Omnichannel Inbox - Krakatau CRM')

@section('content')
    @php($activeConversation = $selectedConversation)
    @php($activeMessages = $activeConversation?->messages?->sortBy('created_at') ?? collect())
    @php($legacyMode = $conversations->isEmpty())

    <section class="service-page omnichannel-workspace">
        <article class="card service-card customer-list-card omni-page-heading">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'inbox'])
            </div>
            <div>
                <h1>Omnichannel Inbox</h1>
                <p>Centralized inbox untuk Email, WhatsApp, Chat, Social, Phone, dan Web.</p>
            </div>
            <a href="{{ route('admin.service.omnichannel.create') }}" class="btn btn-primary">Add Message</a>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="card customer-alert">{{ session('error') }}</div>
        @endif

        <div class="omni-shell">
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
                    </div>
                </form>

                <div class="omni-conversation-list">
                    @if (! $legacyMode)
                        @forelse ($conversations as $conversation)
                            @php($name = $conversation->contact_name ?: $conversation->customer?->name ?: $conversation->lead?->name ?: $conversation->phone_number)
                            @php($initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') ?: 'W')
                            <a href="{{ route('admin.service.omnichannel.index', ['q' => $search, 'filter' => $selectedFilter, 'conversation' => $conversation->id]) }}" class="omni-conversation-item {{ $activeConversation?->id === $conversation->id ? 'active' : '' }}">
                                <span class="omni-avatar">
                                    {{ strtoupper($initials) }}
                                    <i></i>
                                </span>
                                <span class="omni-conversation-main">
                                    <strong>{{ $name }}</strong>
                                    <small>{{ str($conversation->last_message ?: 'Belum ada pesan')->limit(42) }}</small>
                                </span>
                                <span class="omni-conversation-meta">
                                    <time>{{ $conversation->last_message_at?->diffForHumans() ?: '-' }}</time>
                                    @if ($conversation->unread_count > 0)
                                        <b>{{ $conversation->unread_count }}</b>
                                    @endif
                                </span>
                            </a>
                        @empty
                            <div class="omni-empty-mini">Belum ada percakapan WhatsApp.</div>
                        @endforelse
                    @else
                        @forelse ($messages as $message)
                            @php($name = $message->sender_name ?: $message->customer?->name ?: 'Unknown Sender')
                            @php($initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') ?: 'O')
                            <a href="{{ route('admin.service.omnichannel.show', $message) }}" class="omni-conversation-item">
                                <span class="omni-avatar">{{ strtoupper($initials) }}<i></i></span>
                                <span class="omni-conversation-main">
                                    <strong>{{ $name }}</strong>
                                    <small>{{ str($message->message)->limit(42) }}</small>
                                </span>
                                <span class="omni-conversation-meta">
                                    <time>{{ $message->received_at?->diffForHumans() ?: '-' }}</time>
                                    @if ($message->status === 'unread')
                                        <b>1</b>
                                    @endif
                                </span>
                            </a>
                        @empty
                            <div class="omni-empty-mini">Belum ada percakapan.</div>
                        @endforelse
                    @endif
                </div>
            </aside>

            <main class="card omni-chat-panel">
                @if ($activeConversation)
                    @php($chatName = $activeConversation->contact_name ?: $activeConversation->customer?->name ?: $activeConversation->lead?->name ?: $activeConversation->phone_number)
                    <div class="omni-chat-header">
                        <div>
                            <h2>{{ $chatName }}</h2>
                            <p>{{ $activeConversation->phone_number }}</p>
                        </div>
                        <button class="btn btn-primary" type="button">Ambil</button>
                    </div>

                    <div class="omni-chat-thread" id="omni-chat-thread">
                        @forelse ($activeMessages as $chatMessage)
                            <div class="omni-bubble-row {{ $chatMessage->message_type === 'outbound' ? 'outbound' : 'inbound' }}">
                                <div class="omni-bubble">
                                    <p>{{ $chatMessage->message }}</p>
                                    <span>{{ ($chatMessage->received_at ?? $chatMessage->sent_at ?? $chatMessage->created_at)?->format('H:i') }} · {{ ucfirst($chatMessage->status) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="omni-empty-chat">Belum ada pesan dalam percakapan ini.</div>
                        @endforelse
                    </div>

                    <form class="omni-composer" method="POST" action="{{ route('admin.service.omnichannel.reply', $activeConversation) }}">
                        @csrf
                        <button type="button" class="omni-icon-btn" title="Emoji">☺</button>
                        <button type="button" class="omni-icon-btn" title="Attachment">↥</button>
                        <textarea name="message" rows="2" placeholder="Tulis balasan..." required></textarea>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                @else
                    <div class="omni-chat-header">
                        <div>
                            <h2>Pilih percakapan</h2>
                            <p>Pesan WhatsApp inbound akan tampil di sini secara realtime-ready.</p>
                        </div>
                    </div>
                    <div class="omni-chat-thread">
                        <div class="omni-empty-chat">Belum ada percakapan WhatsApp aktif.</div>
                    </div>
                @endif
            </main>

            <aside class="card omni-profile-panel">
                <div class="omni-profile-head">
                    <span class="omni-avatar large">{{ $activeConversation ? strtoupper(mb_substr($activeConversation->contact_name ?: $activeConversation->phone_number, 0, 2)) : 'WA' }}</span>
                    <h2>{{ $activeConversation?->contact_name ?: 'Customer Profile' }}</h2>
                    <p>{{ $activeConversation?->phone_number ?: 'Pilih percakapan untuk melihat detail.' }}</p>
                </div>

                <div class="omni-profile-list">
                    <div><strong>Status</strong><span class="status-badge status-{{ $activeConversation?->status ?? 'baru' }}">{{ ucfirst($activeConversation?->status ?? 'baru') }}</span></div>
                    <div><strong>Prioritas</strong><span>{{ ucfirst($activeConversation?->priority ?? 'medium') }}</span></div>
                    <div><strong>Ditangani oleh</strong><span>{{ $activeConversation?->assigned_to ?: 'Belum diambil' }}</span></div>
                    <div><strong>Tags</strong><span>{{ collect($activeConversation?->tags ?? [])->implode(', ') ?: '-' }}</span></div>
                    <div><strong>Notes</strong><span>{{ $activeConversation?->notes ?: '-' }}</span></div>
                </div>

                <div class="omni-profile-actions">
                    <button class="btn btn-primary" type="button">Ambil Percakapan</button>
                    <a class="btn btn-muted" href="{{ route('admin.service.tickets.create') }}">Buat Ticket</a>
                    <button class="btn btn-muted" type="button">Tandai Selesai</button>
                </div>
            </aside>
        </div>
    </section>

    <script>
        const omniThread = document.getElementById('omni-chat-thread');
        if (omniThread) {
            omniThread.scrollTop = omniThread.scrollHeight;
        }
        window.setTimeout(() => {
            if (!document.querySelector('.omni-composer textarea:focus')) {
                window.location.reload();
            }
        }, 5000);
    </script>
@endsection
