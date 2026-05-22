<li class="menu-sort-node" data-menu-id="{{ $node['id'] }}" draggable="true">
    <div class="menu-sort-node__card">
        <div class="menu-sort-node__main">
            <button type="button" class="menu-sort-handle" aria-label="Drag {{ $node['title'] }}">
                <svg viewBox="0 0 24 24"><path d="M9 6h.01"/><path d="M9 12h.01"/><path d="M9 18h.01"/><path d="M15 6h.01"/><path d="M15 12h.01"/><path d="M15 18h.01"/></svg>
            </button>
            <div class="menu-sort-node__body">
                <strong>{{ $node['title'] }}</strong>
                <small>
                    {{ $node['section'] }} • sort {{ $node['sort_order'] }}
                    @if ($node['route'])
                        • {{ $node['route'] }}
                    @endif
                </small>
            </div>
        </div>
        <div class="menu-sort-node__meta">
            <span class="status-badge {{ $node['is_active'] ? 'status-active' : 'status-pending' }}">
                {{ $node['is_active'] ? 'Active' : 'Inactive' }}
            </span>
            @if ($node['roles'] !== [])
                <span class="role-badge role-badge--admin">{{ implode(', ', $node['roles']) }}</span>
            @else
                <span class="status-badge status-qualified">All roles</span>
            @endif
        </div>
    </div>

    @if ($node['children'] !== [])
        <ol class="menu-sort-list menu-sort-list--child" data-parent-id="{{ $node['id'] }}">
            @foreach ($node['children'] as $childNode)
                @include('admin.system.menus.partials.sort-node', ['node' => $childNode])
            @endforeach
        </ol>
    @endif
</li>
