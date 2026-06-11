@extends('admin.layouts.app')

@section('title', 'Omnichannel Inbox - Krakatau CRM')

@section('content')
    @php($activeConversation = $selectedConversation)
    @php($activeMessages = $activeConversation?->messages?->sortBy('created_at') ?? collect())

    <section class="service-page omnichannel-workspace">
        <article class="card service-card customer-list-card omni-page-heading">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'inbox'])
            </div>
            <div>
                <h1>Omnichannel Inbox</h1>
                <p>Inbox percakapan WhatsApp real dari webhook Meta Cloud API.</p>
            </div>
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

                <form method="POST" action="{{ route('admin.service.omnichannel.bulk-destroy-conversations') }}" class="omni-bulk-form" onsubmit="return confirm('Hapus conversation WhatsApp yang dipilih?')">
                    @csrf
                    @method('DELETE')
                    <div class="omni-bulk-toolbar">
                        <label><input type="checkbox" data-omni-select-all> Pilih Semua</label>
                        <button class="btn btn-sm btn-danger" type="submit">Bulk Delete Conversation</button>
                    </div>

                    <div class="omni-conversation-list">
                        @forelse ($conversations as $conversation)
                            @php($name = $conversation->contact_name ?: $conversation->customer?->name ?: $conversation->lead?->name ?: $conversation->phone_number)
                            @php($initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') ?: 'W')
                            @php($provider = strtolower((string) ($conversation->messages->firstWhere('provider')?->provider ?? 'meta')))
                            @php($providerLabel = $provider === 'meta' ? 'Meta Cloud API' : 'Fonnte')
                            <div class="omni-conversation-row">
                                <label class="omni-select-box" title="Pilih conversation">
                                    <input type="checkbox" name="conversation_ids[]" value="{{ $conversation->id }}">
                                </label>
                                <a href="{{ route('admin.service.omnichannel.index', ['q' => $search, 'filter' => $selectedFilter, 'status' => $selectedStatus, 'conversation' => $conversation->id]) }}" class="omni-conversation-item {{ $activeConversation?->id === $conversation->id ? 'active' : '' }}">
                                    <span class="omni-avatar">
                                        {{ strtoupper($initials) }}
                                        <i></i>
                                    </span>
                                    <span class="omni-conversation-main">
                                        <strong>{{ $name }}</strong>
                                        <em class="omni-provider-badge {{ $provider === 'meta' ? 'meta' : 'fonnte' }}">{{ $providerLabel }}</em>
                                        <small>{{ str($conversation->last_message ?: 'Belum ada pesan')->limit(42) }}</small>
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
                    <div class="omni-chat-header">
                        <div>
                            <h2>{{ $chatName }}</h2>
                            <p>{{ $activeConversation->phone_number }} · <span class="omni-provider-badge {{ $activeProvider === 'meta' ? 'meta' : 'fonnte' }}">{{ $activeProviderLabel }}</span></p>
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

                    <div class="omni-chat-thread" id="omni-chat-thread">
                        @forelse ($activeMessages as $chatMessage)
                            <div class="omni-bubble-row {{ $chatMessage->direction === 'outbound' ? 'outbound' : 'inbound' }}">
                                <div class="omni-bubble">
                                    @if ($chatMessage->media_path || $chatMessage->media_url)
                                        @php($mediaUrl = $chatMessage->media_url ?: \Illuminate\Support\Facades\Storage::disk('public')->url($chatMessage->media_path))
                                        @php($mediaName = $chatMessage->media_original_name ?: basename((string) $chatMessage->media_path))
                                        @if (str_starts_with((string) $chatMessage->media_mime, 'image/'))
                                            <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="omni-media-preview">
                                                <img src="{{ $mediaUrl }}" alt="{{ $mediaName }}">
                                            </a>
                                        @else
                                            <a href="{{ $mediaUrl }}" target="_blank" rel="noopener" class="omni-media-file" download>
                                                <strong>{{ $mediaName }}</strong>
                                                <small>{{ $chatMessage->media_mime ?: 'attachment' }}</small>
                                            </a>
                                        @endif
                                    @endif
                                    @if (trim((string) $chatMessage->message) !== '')
                                        <p>{{ $chatMessage->message }}</p>
                                    @endif
                                    <span>{{ ($chatMessage->received_at ?? $chatMessage->sent_at ?? $chatMessage->created_at)?->format('H:i') }} · {{ ucfirst($chatMessage->status) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="omni-empty-chat">Belum ada pesan dalam percakapan ini.</div>
                        @endforelse
                    </div>

                    <div class="omni-emoji-picker" data-omni-emoji-picker hidden>
                        @foreach (['😀', '😁', '😂', '😊', '👍', '🙏', '👋', '✅', '❌', '🔥', '🎉', '❤️'] as $emoji)
                            <button type="button" data-omni-emoji="{{ $emoji }}">{{ $emoji }}</button>
                        @endforeach
                    </div>
                    <form class="omni-composer" method="POST" action="{{ route('admin.service.omnichannel.reply', $activeConversation) }}" enctype="multipart/form-data">
                        @csrf
                        <button type="button" class="omni-icon-btn" title="Emoji" data-omni-emoji-button>☺</button>
                        <button type="button" class="omni-icon-btn" title="Attachment" data-omni-attachment-button>↥</button>
                        <input type="file" name="attachment" data-omni-attachment-input hidden accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.mp4,.mp3">
                        <textarea name="message" rows="2" placeholder="Tulis balasan..." data-omni-message-input></textarea>
                        <span class="omni-attachment-pill" data-omni-attachment-pill hidden>
                            <span class="omni-attachment-name" data-omni-attachment-name></span>
                            <button type="button" class="omni-attachment-clear" title="Hapus attachment" data-omni-attachment-clear>×</button>
                        </span>
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
                    @if ($activeConversation)
                        @php($profileProvider = strtolower((string) ($activeConversation->messages->firstWhere('provider')?->provider ?? 'meta')))
                        @php($profileProviderLabel = $profileProvider === 'meta' ? 'Meta Cloud API' : 'Fonnte')
                        <div><strong>Provider</strong><span class="omni-provider-badge {{ $profileProvider === 'meta' ? 'meta' : 'fonnte' }}">{{ $profileProviderLabel }}</span></div>
                    @endif
                    <div><strong>Status</strong><span class="status-badge status-{{ $activeConversation?->status ?? 'open' }}">{{ ucfirst($activeConversation?->status ?? 'open') }}</span></div>
                    <div><strong>Prioritas</strong><span>{{ ucfirst($activeConversation?->priority ?? 'medium') }}</span></div>
                    <div><strong>Ditangani oleh</strong><span>{{ $activeConversation?->assigned_to ?: 'Belum diambil' }}</span></div>
                    <div><strong>Tags</strong><span>{{ collect($activeConversation?->tags ?? [])->implode(', ') ?: '-' }}</span></div>
                    <div><strong>Notes</strong><span>{{ $activeConversation?->notes ?: '-' }}</span></div>
                </div>

                <div class="omni-profile-actions">
                    @if ($activeConversation && ! $activeConversation->assigned_to)
                        <form method="POST" action="{{ route('admin.service.omnichannel.assign', $activeConversation) }}">
                            @csrf
                            <button class="btn btn-primary" type="submit">Ambil Percakapan</button>
                        </form>
                    @elseif ($activeConversation)
                        <span class="omni-assigned-note">Sudah diambil oleh {{ $activeConversation->assigned_to }}</span>
                    @endif
                    <a class="btn btn-muted" href="{{ route('admin.service.tickets.create') }}">Buat Ticket</a>
                    @if ($activeConversation)
                        <form method="POST" action="{{ route('admin.service.omnichannel.resolve', $activeConversation) }}">
                            @csrf
                            <button class="btn btn-muted" type="submit">Tandai Selesai</button>
                        </form>
                        <form method="POST" action="{{ route('admin.service.omnichannel.destroy-conversation', $activeConversation) }}" onsubmit="return confirm('Hapus conversation WhatsApp ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">Hapus Conversation</button>
                        </form>
                    @endif
                </div>
            </aside>
        </div>
    </section>

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
        const attachmentButton = document.querySelector('[data-omni-attachment-button]');
        const attachmentInput = document.querySelector('[data-omni-attachment-input]');
        const attachmentPill = document.querySelector('[data-omni-attachment-pill]');
        const attachmentName = document.querySelector('[data-omni-attachment-name]');
        const attachmentClear = document.querySelector('[data-omni-attachment-clear]');
        const emojiButton = document.querySelector('[data-omni-emoji-button]');
        const emojiPicker = document.querySelector('[data-omni-emoji-picker]');
        const messageInput = document.querySelector('[data-omni-message-input]');
        emojiButton?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            emojiPicker.hidden = !emojiPicker.hidden;
        });
        emojiPicker?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const emojiTarget = event.target.closest('[data-omni-emoji]');
            const emoji = emojiTarget?.dataset?.omniEmoji;
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
        window.setTimeout(() => {
            const hasSelectedAttachment = (attachmentInput?.files?.length || 0) > 0;
            const isEmojiPickerOpen = !!emojiPicker && !emojiPicker.hidden;
            if (!hasSelectedAttachment && !isEmojiPickerOpen && !document.querySelector('.omni-composer textarea:focus')) {
                window.location.reload();
            }
        }, 5000);
    </script>

    <style>
        .omni-bulk-form{display:grid;gap:.65rem}
        .omni-bulk-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.65rem;color:#6f6b7d;font-size:.78rem;font-weight:800}
        .omni-bulk-toolbar label{display:inline-flex;align-items:center;gap:.35rem}
        .omni-conversation-row{display:grid;grid-template-columns:auto minmax(0,1fr);align-items:stretch;gap:.45rem}
        .omni-select-box{display:grid;place-items:center;min-width:1.6rem}
        .omni-provider-badge{display:inline-flex;align-items:center;justify-content:center;width:max-content;border-radius:999px;padding:.18rem .5rem;font-size:.68rem;font-style:normal;font-weight:900;line-height:1;white-space:nowrap}
        .omni-provider-badge.meta{background:#eef6ff;color:#1677c6}
        .omni-provider-badge.fonnte{background:#e8f8ef;color:#168a49}
        .omni-assigned-note{display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(24,39,75,.12);border-radius:.5rem;padding:.62rem .8rem;background:#f8f8fb;color:#5d596c;font-size:.78rem;font-weight:900}
        .omni-composer{grid-template-columns:auto auto minmax(0,1fr) minmax(0,9rem) auto}
        .omni-emoji-picker{display:grid;grid-template-columns:repeat(6,2rem);gap:.35rem;align-self:start;width:max-content;max-width:100%;margin:.75rem 1rem 0;padding:.6rem;border:1px solid rgba(24,39,75,.12);border-radius:.5rem;background:#fff;box-shadow:0 10px 24px rgba(24,39,75,.12)}
        .omni-emoji-picker[hidden]{display:none}
        .omni-emoji-picker button{display:grid;place-items:center;width:2rem;height:2rem;border:0;border-radius:.45rem;background:#f8f8fb;cursor:pointer;font-size:1.1rem;line-height:1}
        .omni-emoji-picker button:hover{background:#eef6ff}
        .omni-media-preview{display:block;margin-bottom:.45rem}
        .omni-media-preview img{display:block;max-width:min(18rem,100%);max-height:14rem;border-radius:.5rem;object-fit:cover}
        .omni-media-file{display:grid;gap:.2rem;margin-bottom:.45rem;padding:.65rem;border:1px solid rgba(24,39,75,.12);border-radius:.5rem;background:rgba(255,255,255,.72);color:inherit;text-decoration:none}
        .omni-media-file strong{font-size:.86rem}
        .omni-media-file small{color:#6f6b7d}
        .omni-attachment-pill{display:inline-flex;align-items:center;gap:.35rem;min-width:0;max-width:9rem;border:1px solid rgba(24,39,75,.12);border-radius:.5rem;padding:.35rem .45rem;background:#f8f8fb;color:#6f6b7d;font-size:.75rem;font-weight:800}
        .omni-attachment-name{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .omni-attachment-clear{display:grid;place-items:center;width:1.15rem;height:1.15rem;border:0;border-radius:999px;background:#e7e5ef;color:#5d596c;cursor:pointer;font-weight:900;line-height:1}
    </style>
@endsection
