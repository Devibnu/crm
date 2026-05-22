<li class="menu-preview-node">
    <div class="menu-preview-node__row">
        <span class="menu-preview-node__icon">
            @if (!empty($node['icon']))
                <small>{{ $node['icon'] }}</small>
            @else
                <small>icon</small>
            @endif
        </span>
        <div class="menu-preview-node__copy">
            <strong>{{ $node['title'] }}</strong>
            @if (!empty($node['route']))
                <small>{{ $node['route'] }}</small>
            @endif
        </div>
    </div>

    @if (!empty($node['children']))
        <ul class="menu-preview-tree menu-preview-tree--child">
            @foreach ($node['children'] as $childNode)
                @include('admin.system.menus.partials.preview-tree', ['node' => $childNode])
            @endforeach
        </ul>
    @endif
</li>
