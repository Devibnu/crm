<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));
        $channel = trim((string) $request->query('channel', ''));

        $tickets = Ticket::query()
            ->with('customer:id,name')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->filter('status', $status, $this->statusOptions())
            ->filter('priority', $priority, $this->priorityOptions())
            ->filter('channel', $channel, $this->channelOptions())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => Ticket::query()->count(),
            'open' => Ticket::query()->where('status', 'open')->count(),
            'in_progress' => Ticket::query()->where('status', 'in_progress')->count(),
            'resolved' => Ticket::query()->where('status', 'resolved')->count(),
        ];

        return view('admin.service.tickets.index', [
            'tickets' => $tickets,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedPriority' => $priority,
            'selectedChannel' => $channel,
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'channelOptions' => $this->channelOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.service.tickets.create', [
            'ticket' => null,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'channelOptions' => $this->channelOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $validated['ticket_number'] = $this->generateTicketNumber();

        $ticket = Ticket::create($validated);

        return redirect()
            ->route('admin.service.tickets.show', $ticket)
            ->with('success', 'Ticket berhasil ditambahkan.');
    }

    public function show(Ticket $ticket): View
    {
        return view('admin.service.tickets.show', [
            'ticket' => $ticket->load('customer:id,name'),
        ]);
    }

    public function edit(Ticket $ticket): View
    {
        return view('admin.service.tickets.edit', [
            'ticket' => $ticket,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'channelOptions' => $this->channelOptions(),
        ]);
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $ticket->update($validated);

        return redirect()
            ->route('admin.service.tickets.show', $ticket)
            ->with('success', 'Ticket berhasil diperbarui.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $ticket->delete();

        return redirect()
            ->route('admin.service.tickets.index')
            ->with('success', 'Ticket berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in($this->priorityOptions())],
            'status' => ['required', Rule::in($this->statusOptions())],
            'channel' => ['required', Rule::in($this->channelOptions())],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'due_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date'],
        ]);

        $validated['customer_id'] = $validated['customer_id'] ?? null;

        return $validated;
    }

    protected function generateTicketNumber(): string
    {
        do {
            $number = 'TCK-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Ticket::query()->where('ticket_number', $number)->exists());

        return $number;
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'];
    }

    /**
     * @return array<int, string>
     */
    protected function priorityOptions(): array
    {
        return ['low', 'medium', 'high', 'urgent'];
    }

    /**
     * @return array<int, string>
     */
    protected function channelOptions(): array
    {
        return ['email', 'phone', 'whatsapp', 'web', 'social', 'walk_in'];
    }
}
