@extends('admin.layouts.app')

@section('title', 'Lead Scoring & Routing - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Lead Scoring & Routing - Krakatau CRM" data-doc-title-id="Lead Scoring & Routing - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'scoring'])
            </div>
            <div>
                <h1 data-lang-en="Lead Scoring & Routing" data-lang-id="Lead Scoring & Routing">Lead Scoring & Routing</h1>
                <p data-lang-en="Manage lead scoring and automatic distribution rules to the sales team." data-lang-id="Kelola scoring lead dan aturan distribusi otomatis ke tim sales.">Kelola scoring lead dan rule distribusi otomatis ke sales team.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Rules" data-lang-id="Total Aturan">Total Rules</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All scoring rules" data-lang-id="Semua aturan scoring">Semua scoring rule</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Active Rules" data-lang-id="Aturan Aktif">Active Rules</span>
                <strong>{{ number_format($summary['active']) }}</strong>
                <small data-lang-en="Active rules" data-lang-id="Aturan aktif">Rule aktif</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Auto Assign Rules" data-lang-id="Aturan Auto Assign">Auto Assign Rules</span>
                <strong>{{ number_format($summary['auto_assign']) }}</strong>
                <small data-lang-en="Automatic routing" data-lang-id="Routing otomatis">Routing otomatis</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Total Executions" data-lang-id="Total Eksekusi">Total Executions</span>
                <strong>{{ number_format($summary['executions']) }}</strong>
                <small data-lang-en="Total scoring executions" data-lang-id="Total eksekusi scoring">Total eksekusi scoring</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Lead Scoring Rules" data-lang-id="Aturan Lead Scoring">Lead Scoring Rules</h2>
                    <p data-lang-en="Search by name or notes, then filter by trigger source, priority, and status." data-lang-id="Cari berdasarkan nama atau catatan, lalu filter berdasarkan sumber trigger, prioritas, dan status.">Search name atau notes, lalu filter trigger source, priority, dan status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.lead-scoring.create') }}" class="btn btn-primary" data-lang-en="Add Rule" data-lang-id="Tambah Aturan">Add Rule</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.lead-scoring.index') }}" class="sales-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name or notes" aria-label="Search lead scoring rules" data-placeholder-en="Name or notes" data-placeholder-id="Nama atau catatan" data-title-en="Search lead scoring rules" data-title-id="Cari aturan lead scoring">
                </label>
                <label class="field">
                    <span data-lang-en="Trigger Source" data-lang-id="Sumber Trigger">Trigger Source</span>
                    <select name="trigger_source">
                        <option value="" data-lang-en="All triggers" data-lang-id="Semua trigger">All triggers</option>
                        @foreach ($triggerOptions as $trigger)
                            <option value="{{ $trigger }}" @selected($selectedTrigger === $trigger)>{{ ucwords(str_replace('_', ' ', $trigger)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Priority" data-lang-id="Prioritas">Priority</span>
                    <select name="priority">
                        <option value="" data-lang-en="All priorities" data-lang-id="Semua prioritas">All priorities</option>
                        @foreach ($priorityOptions as $priority)
                            <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
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
                    @if ($search || $selectedTrigger || $selectedPriority || $selectedStatus)
                        <a href="{{ route('admin.marketing.lead-scoring.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Rule Name" data-lang-id="Nama Aturan">Rule Name</th>
                            <th data-lang-en="Trigger Source" data-lang-id="Sumber Trigger">Trigger Source</th>
                            <th data-lang-en="Score" data-lang-id="Skor">Score</th>
                            <th data-lang-en="Routing Team" data-lang-id="Tim Routing">Routing Team</th>
                            <th data-lang-en="Routing User" data-lang-id="User Routing">Routing User</th>
                            <th data-lang-en="Priority" data-lang-id="Prioritas">Priority</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Auto Assign" data-lang-id="Auto Assign">Auto Assign</th>
                            <th data-lang-en="Execution Count" data-lang-id="Jumlah Eksekusi">Execution Count</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rules as $rule)
                            <tr>
                                <td><a href="{{ route('admin.marketing.lead-scoring.show', $rule) }}" class="sales-title-link">{{ $rule->name }}</a></td>
                                <td><span class="status-badge trigger-{{ $rule->trigger_source }}">{{ ucwords(str_replace('_', ' ', $rule->trigger_source)) }}</span></td>
                                <td>{{ number_format($rule->score_value) }}</td>
                                <td>{{ $rule->routing_team ?: '-' }}</td>
                                <td>{{ $rule->routing_user ?: '-' }}</td>
                                <td><span class="status-badge priority-{{ $rule->priority }}">{{ ucfirst($rule->priority) }}</span></td>
                                <td><span class="status-badge status-{{ $rule->status }}">{{ ucfirst($rule->status) }}</span></td>
                                <td><span class="status-badge status-{{ $rule->auto_assign ? 'active' : 'inactive' }}" data-lang-en="{{ $rule->auto_assign ? 'Yes' : 'No' }}" data-lang-id="{{ $rule->auto_assign ? 'Ya' : 'Tidak' }}">{{ $rule->auto_assign ? 'Yes' : 'No' }}</span></td>
                                <td>{{ number_format($rule->execution_count) }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.lead-scoring.show', $rule) }}" class="btn btn-sm btn-muted" data-lang-en="Show" data-lang-id="Lihat">Show</a>
                                        <a href="{{ route('admin.marketing.lead-scoring.edit', $rule) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.lead-scoring.destroy', $rule) }}" data-confirm-en="Delete this lead scoring rule?" data-confirm-id="Hapus aturan lead scoring ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this lead scoring rule?');">
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
                                        <strong data-lang-en="No scoring rules yet" data-lang-id="Belum ada aturan scoring">Belum ada scoring rule</strong>
                                        <span data-lang-en="Add the first rule to start scoring and routing leads." data-lang-id="Tambahkan aturan pertama untuk mulai scoring dan routing lead.">Tambahkan rule pertama untuk mulai scoring dan routing lead.</span>
                                        <a href="{{ route('admin.marketing.lead-scoring.create') }}" class="btn btn-primary" data-lang-en="Add Rule" data-lang-id="Tambah Aturan">Add Rule</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rules->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info" data-lang-en="Showing {{ $rules->firstItem() }}-{{ $rules->lastItem() }} of {{ $rules->total() }} rules" data-lang-id="Menampilkan {{ $rules->firstItem() }}-{{ $rules->lastItem() }} dari {{ $rules->total() }} aturan">
                        Menampilkan {{ $rules->firstItem() }}-{{ $rules->lastItem() }} dari {{ $rules->total() }} rule
                    </div>
                    <div class="pagination-links">
                        @if ($rules->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</span>
                        @else
                            <a href="{{ $rules->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</a>
                        @endif

                        @foreach ($rules->getUrlRange(max(1, $rules->currentPage() - 2), min($rules->lastPage(), $rules->currentPage() + 2)) as $page => $url)
                            @if ($page === $rules->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($rules->hasMorePages())
                            <a href="{{ $rules->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Berikutnya">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Berikutnya">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
