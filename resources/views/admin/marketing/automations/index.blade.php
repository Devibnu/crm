@extends('admin.layouts.app')

@section('title', 'Automation & Nurturing - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Automation & Nurturing - Krakatau CRM" data-doc-title-id="Otomasi & Nurturing - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'automation'])
            </div>
            <div>
                <h1 data-lang-en="Automation & Nurturing" data-lang-id="Otomasi & Nurturing">Automation & Nurturing</h1>
                <p data-lang-en="Manage automation rules for lead nurturing and marketing follow-ups." data-lang-id="Kelola aturan otomasi untuk nurturing lead dan tindak lanjut marketing.">Kelola rule automation untuk nurturing lead dan follow-up marketing.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Automations" data-lang-id="Total Otomasi">Total Automations</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All automation rules" data-lang-id="Semua aturan otomasi">Semua rule automation</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Active Automations" data-lang-id="Otomasi Aktif">Active Automations</span>
                <strong>{{ number_format($summary['active']) }}</strong>
                <small data-lang-en="Rules currently active" data-lang-id="Aturan yang sedang aktif">Rule sedang aktif</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Paused Automations" data-lang-id="Otomasi Dijeda">Paused Automations</span>
                <strong>{{ number_format($summary['paused']) }}</strong>
                <small data-lang-en="Paused rules" data-lang-id="Aturan yang dijeda">Rule ditunda</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Total Executed" data-lang-id="Total Eksekusi">Total Executed</span>
                <strong>{{ number_format($summary['executed']) }}</strong>
                <small data-lang-en="Total rule executions" data-lang-id="Total eksekusi aturan">Total eksekusi rule</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Automation Rules" data-lang-id="Aturan Otomasi">Automation Rules</h2>
                    <p data-lang-en="Search by name or notes, then filter by trigger, action, and status." data-lang-id="Cari berdasarkan nama atau catatan, lalu filter berdasarkan trigger, action, dan status.">Search name atau notes, lalu filter trigger, action, dan status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.automations.create') }}" class="btn btn-primary" data-lang-en="Add Automation" data-lang-id="Tambah Otomasi">Add Automation</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.automations.index') }}" class="sales-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name or notes" aria-label="Search automations" data-placeholder-en="Name or notes" data-placeholder-id="Nama atau catatan" data-title-en="Search automations" data-title-id="Cari otomasi">
                </label>
                <label class="field">
                    <span data-lang-en="Trigger" data-lang-id="Pemicu">Trigger</span>
                    <select name="trigger_type">
                        <option value="" data-lang-en="All triggers" data-lang-id="Semua pemicu">All triggers</option>
                        @foreach ($triggerOptions as $trigger)
                            <option value="{{ $trigger }}" @selected($selectedTrigger === $trigger)>{{ ucwords(str_replace('_', ' ', $trigger)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Action" data-lang-id="Aksi">Action</span>
                    <select name="action_type">
                        <option value="" data-lang-en="All actions" data-lang-id="Semua aksi">All actions</option>
                        @foreach ($actionOptions as $action)
                            <option value="{{ $action }}" @selected($selectedAction === $action)>{{ ucwords(str_replace('_', ' ', $action)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <select name="status">
                        <option value="" data-lang-en="All statuses" data-lang-id="Semua status">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Apply Filter" data-lang-id="Terapkan Filter">Apply Filter</button>
                    @if ($search || $selectedTrigger || $selectedAction || $selectedStatus)
                        <a href="{{ route('admin.marketing.automations.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Name" data-lang-id="Nama">Name</th>
                            <th data-lang-en="Campaign" data-lang-id="Campaign">Campaign</th>
                            <th data-lang-en="Audience Segment" data-lang-id="Segmen Audiens">Audience Segment</th>
                            <th data-lang-en="Trigger" data-lang-id="Pemicu">Trigger</th>
                            <th data-lang-en="Action" data-lang-id="Aksi">Action</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Delay" data-lang-id="Jeda">Delay</th>
                            <th data-lang-en="Executed" data-lang-id="Dieksekusi">Executed</th>
                            <th data-lang-en="Last Executed" data-lang-id="Eksekusi Terakhir">Last Executed</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($automations as $automation)
                            <tr>
                                <td><a href="{{ route('admin.marketing.automations.show', $automation) }}" class="sales-title-link">{{ $automation->name }}</a></td>
                                <td>{{ $automation->marketingCampaign?->name ?: '-' }}</td>
                                <td>{{ $automation->audienceSegment?->name ?: '-' }}</td>
                                <td><span class="status-badge trigger-{{ $automation->trigger_type }}">{{ ucwords(str_replace('_', ' ', $automation->trigger_type)) }}</span></td>
                                <td><span class="status-badge action-{{ $automation->action_type }}">{{ ucwords(str_replace('_', ' ', $automation->action_type)) }}</span></td>
                                <td><span class="status-badge status-{{ $automation->status }}">{{ ucfirst($automation->status) }}</span></td>
                                <td>{{ number_format($automation->delay_minutes) }} min</td>
                                <td>{{ number_format($automation->executed_count) }}</td>
                                <td>{{ $automation->last_executed_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.automations.show', $automation) }}" class="btn btn-sm btn-muted" data-lang-en="Show" data-lang-id="Lihat">Show</a>
                                        <a href="{{ route('admin.marketing.automations.edit', $automation) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.automations.destroy', $automation) }}" data-confirm-en="Delete this automation?" data-confirm-id="Hapus otomasi ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this automation?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No automations yet" data-lang-id="Belum ada otomasi">Belum ada automation</strong>
                                        <span data-lang-en="Add the first rule to start automated lead nurturing." data-lang-id="Tambahkan aturan pertama untuk mulai nurturing lead secara otomatis.">Tambahkan rule pertama untuk mulai nurturing lead otomatis.</span>
                                        <a href="{{ route('admin.marketing.automations.create') }}" class="btn btn-primary" data-lang-en="Add Automation" data-lang-id="Tambah Otomasi">Add Automation</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($automations->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info" data-lang-en="Showing {{ $automations->firstItem() }}-{{ $automations->lastItem() }} of {{ $automations->total() }} automations" data-lang-id="Menampilkan {{ $automations->firstItem() }}-{{ $automations->lastItem() }} dari {{ $automations->total() }} otomasi">
                        Menampilkan {{ $automations->firstItem() }}-{{ $automations->lastItem() }} dari {{ $automations->total() }} automation
                    </div>
                    <div class="pagination-links">
                        @if ($automations->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</span>
                        @else
                            <a href="{{ $automations->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</a>
                        @endif

                        @foreach ($automations->getUrlRange(max(1, $automations->currentPage() - 2), min($automations->lastPage(), $automations->currentPage() + 2)) as $page => $url)
                            @if ($page === $automations->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($automations->hasMorePages())
                            <a href="{{ $automations->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Berikutnya">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Berikutnya">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
