@extends('admin.layouts.app')

@section('title', 'Preview Menu - Krakatau CRM')

@section('content')
<span hidden data-doc-title-en="Menu Preview - Krakatau CRM" data-doc-title-id="Preview Menu - Krakatau CRM"></span>
<section class="service-page customer-list-page">
    <article class="card service-card customer-list-card">
        <div class="service-card-icon">
            @include('admin.partials.sidebar-icon', ['icon' => 'menu'])
        </div>
        <div>
            <h1 data-lang-en="Menu Preview & Reorder" data-lang-id="Preview & Ubah Urutan Menu">Menu Preview & Reorder</h1>
            <p data-lang-en="Preview desktop and mobile navigation per role, then reorder menus with tree-based drag and drop." data-lang-id="Preview navigasi desktop dan mobile per role, lalu atur urutan menu dengan drag and drop berbasis tree.">Preview navigasi desktop dan mobile per role, lalu atur urutan menu dengan drag and drop berbasis tree.</p>
        </div>
    </article>

    @if (session('success'))
        <div class="card customer-alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="card customer-alert danger">{{ session('error') }}</div>
    @endif

    <article class="card users-table-card">
        <div class="users-table-toolbar">
            <form method="GET" action="{{ route('admin.system.menus.preview') }}" class="users-search-form">
                <select name="role" aria-label="Preview role" data-title-en="Preview role" data-title-id="Preview role">
                    <option value="" data-lang-en="All roles" data-lang-id="Semua role">Semua role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary" data-lang-en="Preview Role" data-lang-id="Preview Role">Preview Role</button>
            </form>
            <div class="table-actions">
                <a href="{{ route('admin.system.menus.index') }}" class="btn btn-muted" data-lang-en="Back to List" data-lang-id="Kembali ke List">Back to List</a>
                <a href="{{ route('admin.system.menus.create') }}" class="btn btn-primary" data-lang-en="Add Menu" data-lang-id="Tambah Menu">Add Menu</a>
            </div>
        </div>
    </article>

    <div class="menu-preview-layout">
        <article class="card menu-preview-card">
            <div class="menu-preview-card__head">
                <div>
                    <h2 data-lang-en="Desktop Sidebar Preview" data-lang-id="Preview Sidebar Desktop">Desktop Sidebar Preview</h2>
                    <p>
                        @if ($selectedRole !== '')
                            <span data-lang-en="Role" data-lang-id="Role">Role</span>: {{ $selectedRole }}
                        @else
                            <span data-lang-en="All active roles" data-lang-id="Semua role aktif">Semua role aktif</span>
                        @endif
                    </p>
                </div>
                <span class="status-badge status-qualified" data-lang-en="Desktop" data-lang-id="Desktop">Desktop</span>
            </div>
            <div class="menu-preview-shell">
                <div class="menu-preview-sidebar-shell">
                    <div class="menu-preview-brand">
                        <span class="menu-preview-brand__mark">V</span>
                        <div>
                            <strong>vuexy</strong>
                            <small>Premium navigation</small>
                        </div>
                    </div>
                    <ul class="menu-preview-tree">
                        @forelse ($navigation as $node)
                            @include('admin.system.menus.partials.preview-tree', ['node' => $node])
                        @empty
                            <li class="menu-preview-empty" data-lang-en="No menus are available for this role." data-lang-id="Tidak ada menu untuk role ini.">Tidak ada menu untuk role ini.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </article>

        <article class="card menu-preview-card">
            <div class="menu-preview-card__head">
                <div>
                    <h2 data-lang-en="Mobile Bottom Navigation" data-lang-id="Bottom Navigation Mobile">Mobile Bottom Navigation</h2>
                    <p data-lang-en="Maximum five primary items, matching the current Vuexy implementation." data-lang-id="Maksimal lima item utama seperti implementasi Vuexy saat ini.">Maksimal lima item utama seperti implementasi Vuexy saat ini.</p>
                </div>
                <span class="status-badge status-active" data-lang-en="Mobile" data-lang-id="Mobile">Mobile</span>
            </div>
            <div class="menu-mobile-preview-shell">
                @forelse ($bottomNavigation as $item)
                    <div class="menu-mobile-preview-item">
                        <span>{{ $item['icon'] ?: 'icon' }}</span>
                        <strong>{{ $item['title'] }}</strong>
                    </div>
                @empty
                    <div class="menu-preview-empty" data-lang-en="Bottom navigation has no items for this role yet." data-lang-id="Bottom navigation belum memiliki item untuk role ini.">Bottom navigation belum memiliki item untuk role ini.</div>
                @endforelse
            </div>
        </article>
    </div>

    <form method="POST" action="{{ route('admin.system.menus.reorder') }}" class="card users-table-card" id="menu-reorder-form">
        @csrf
        <input type="hidden" name="groups_json" id="menu-groups-json" value="">

        <div class="customer-alert info users-role-info" data-lang-en="Drag menus within the same parent to change the order. When finished, save the menu order." data-lang-id="Drag menu di dalam parent yang sama untuk mengubah urutan. Setelah selesai, klik simpan urutan.">
            Drag menu di dalam parent yang sama untuk mengubah urutan. Setelah selesai, klik simpan urutan.
        </div>

        <div class="users-table-toolbar">
            <div>
                <h2 data-lang-en="Tree Reorder Canvas" data-lang-id="Kanvas Ubah Urutan Tree">Tree Reorder Canvas</h2>
                <p data-lang-en="Parent and subtree move together. The parent-child structure does not change." data-lang-id="Parent dan subtree ikut berpindah bersama. Struktur parent-child tidak berubah.">Parent dan subtree ikut berpindah bersama. Struktur parent-child tidak berubah.</p>
            </div>
            <div class="table-actions">
                <button type="button" class="btn btn-muted" data-menu-reset-order data-lang-en="Reset Visual Position" data-lang-id="Reset Posisi Visual">Reset Visual Position</button>
                <button type="submit" class="btn btn-primary" data-lang-en="Save Menu Order" data-lang-id="Simpan Urutan Menu">Save Menu Order</button>
            </div>
        </div>

        <ol class="menu-sort-list" data-parent-id="">
            @foreach ($sortableTree as $node)
                @include('admin.system.menus.partials.sort-node', ['node' => $node])
            @endforeach
        </ol>
    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('menu-reorder-form');
        const payloadInput = document.getElementById('menu-groups-json');
        const resetButton = document.querySelector('[data-menu-reset-order]');

        if (!form || !payloadInput) {
            return;
        }

        const initialMarkup = form.querySelector('.menu-sort-list')?.innerHTML ?? '';
        let draggedNode = null;

        const getNodeAfterPointer = (container, y) => {
            const draggableElements = [...container.querySelectorAll(':scope > .menu-sort-node:not(.is-dragging)')];

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;

                if (offset < 0 && offset > closest.offset) {
                    return { offset, element: child };
                }

                return closest;
            }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
        };

        form.querySelectorAll('.menu-sort-node').forEach(node => {
            node.addEventListener('dragstart', () => {
                draggedNode = node;
                node.classList.add('is-dragging');
            });

            node.addEventListener('dragend', () => {
                node.classList.remove('is-dragging');
                draggedNode = null;
            });
        });

        form.querySelectorAll('.menu-sort-list').forEach(list => {
            list.addEventListener('dragover', event => {
                event.preventDefault();

                if (!draggedNode || draggedNode.parentElement !== list) {
                    return;
                }

                const afterElement = getNodeAfterPointer(list, event.clientY);

                if (!afterElement) {
                    list.appendChild(draggedNode);
                } else {
                    list.insertBefore(draggedNode, afterElement);
                }
            });
        });

        form.addEventListener('submit', event => {
            const groups = [...form.querySelectorAll('.menu-sort-list')].map(list => {
                return {
                    parent_id: list.dataset.parentId === '' ? null : Number(list.dataset.parentId),
                    ordered_ids: [...list.querySelectorAll(':scope > .menu-sort-node')].map(node => Number(node.dataset.menuId)),
                };
            }).filter(group => group.ordered_ids.length > 0);

            if (groups.length === 0) {
                event.preventDefault();
                return;
            }

            payloadInput.value = JSON.stringify(groups);
        });

        resetButton?.addEventListener('click', () => {
            const rootList = form.querySelector('.menu-sort-list');

            if (rootList) {
                rootList.innerHTML = initialMarkup;
                window.location.reload();
            }
        });
    });
</script>
@endsection
