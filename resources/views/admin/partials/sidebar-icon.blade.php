@switch($icon)
    @case('cart')
        <svg viewBox="0 0 24 24"><path d="M4 5h2l2 10h10l2-7H7"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
        @break
    @case('cap')
        <svg viewBox="0 0 24 24"><path d="m3 8 9-4 9 4-9 4-9-4Z"/><path d="M7 10v5c3 2 7 2 10 0v-5"/></svg>
        @break
    @case('truck')
        <svg viewBox="0 0 24 24"><path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="17" cy="19" r="2"/></svg>
        @break
    @case('mail')
        <svg viewBox="0 0 24 24"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
        @break
    @case('chat')
        <svg viewBox="0 0 24 24"><path d="M4 5h16v11H8l-4 4z"/></svg>
        @break
    @case('calendar')
        <svg viewBox="0 0 24 24"><path d="M4 5h16v15H4z"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M4 10h16"/></svg>
        @break
    @case('kanban')
        <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M9 5v14"/><path d="M15 5v14"/></svg>
        @break
    @case('invoice')
        <svg viewBox="0 0 24 24"><path d="M7 3h10v18l-2-1-2 1-2-1-2 1-2-1z"/><path d="M9 8h6"/><path d="M9 12h6"/><path d="M9 16h4"/></svg>
        @break
    @case('user')
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
        @break
    @case('lock')
        <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
        @break
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
    @case('book')
        <svg viewBox="0 0 24 24"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 1 4 17.5z"/><path d="M4 17.5A2.5 2.5 0 0 1 6.5 15H20"/></svg>
        @break
    @case('lead')
        <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M3 21a6 6 0 0 1 12 0"/><path d="M17 11h4"/><path d="M19 9v4"/></svg>
        @break
    @case('opportunity')
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/><path d="m16 4 2 2"/></svg>
        @break
    @case('pipeline')
        <svg viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M4 19h16"/><path d="m7 15 4-4 3 3 5-7"/><path d="M16 7h3v3"/></svg>
        @break
    @case('activity')
        <svg viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M8 17v-6"/><path d="M12 17V7"/><path d="M16 17v-4"/><path d="M20 17V9"/></svg>
        @break
    @case('deal')
        <svg viewBox="0 0 24 24"><path d="M7 11 4 8l4-4 3 3"/><path d="m17 13 3 3-4 4-3-3"/><path d="m8 12 4 4 5-5-4-4z"/></svg>
        @break
    @case('analysis')
        <svg viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M4 19h16"/><path d="m7 15 3-3 3 2 5-7"/><path d="m15 7 3-1 1 3"/></svg>
        @break
    @case('campaign')
        <svg viewBox="0 0 24 24"><path d="M4 11v4l4 1 8 4V6L8 10z"/><path d="M16 9a4 4 0 0 1 0 8"/><path d="M8 10v6"/></svg>
        @break
    @case('audience')
        <svg viewBox="0 0 24 24"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M3 20a5 5 0 0 1 10 0"/><path d="M11 20a5 5 0 0 1 10 0"/></svg>
        @break
    @case('execution')
        <svg viewBox="0 0 24 24"><path d="M5 12h10"/><path d="m12 7 5 5-5 5"/><path d="M4 5h16v14H4z"/></svg>
        @break
    @case('landing')
        <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M4 9h16"/><path d="M8 13h5"/><path d="M8 16h8"/><path d="M16 13h2"/></svg>
        @break
    @case('social')
        <svg viewBox="0 0 24 24"><circle cx="6" cy="12" r="3"/><circle cx="18" cy="6" r="3"/><circle cx="18" cy="18" r="3"/><path d="m8.7 10.7 6.6-3.4"/><path d="m8.7 13.3 6.6 3.4"/></svg>
        @break
    @default
        <svg viewBox="0 0 24 24"><path d="M6 3h9l3 3v15H6z"/><path d="M15 3v4h4"/></svg>
@endswitch
