<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateTicketRequest;
use App\Http\Requests\Admin\UpdateTicketRequest;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\WhatsAppConversation;
use App\Services\Sla\TicketSlaService;
use App\Services\Tickets\TicketNumberGenerator;
use App\Services\Tickets\TicketWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(
        protected TicketNumberGenerator $ticketNumberGenerator,
        protected TicketWorkflowService $ticketWorkflowService,
        protected TicketSlaService $ticketSlaService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));
        $channel = trim((string) $request->query('channel', ''));

        $tickets = Ticket::query()
            ->with('customer:id,name')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->filter('status', $status, Ticket::statusOptions())
            ->filter('priority', $priority, Ticket::priorityOptions())
            ->filter('channel', $channel, Ticket::channelOptions())
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
            'statusOptions' => Ticket::statusOptions(),
            'priorityOptions' => Ticket::priorityOptions(),
            'channelOptions' => Ticket::channelOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(Request $request): View
    {
        $conversation = null;

        if ($request->filled('conversation_id')) {
            $conversation = WhatsAppConversation::with('customer')
                ->find($request->integer('conversation_id'));
        }

        return view('admin.service.tickets.create', [
            'ticket' => null,
            'conversation' => $conversation,
            'prefillCustomer' => $conversation?->customer,
            'prefillSubject' => $conversation ? 'WhatsApp - '.$this->conversationName($conversation) : null,
            'prefillDescription' => $conversation?->last_message,
            'prefillChannel' => $conversation ? 'whatsapp' : null,
            'prefillPriority' => $conversation ? 'medium' : null,
            'prefillStatus' => $conversation ? 'open' : null,
            'prefillAssignedTo' => $conversation ? $request->user()?->name : null,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => Ticket::statusOptions(),
            'priorityOptions' => Ticket::priorityOptions(),
            'channelOptions' => Ticket::channelOptions(),
        ]);
    }

    public function store(CreateTicketRequest $request): RedirectResponse
    {
        $validated = $request->ticketData();
        $validated['ticket_number'] = $this->ticketNumberGenerator->generate();

        $ticket = Ticket::create($validated);
        $this->ticketSlaService->apply($ticket);

        return redirect()
            ->route('admin.service.tickets.show', $ticket)
            ->with('success', 'Ticket berhasil ditambahkan.');
    }

    public function show(Ticket $ticket): View
    {
        return view('admin.service.tickets.show', [
            'ticket' => $ticket->load([
                'customer:id,name',
                'sourceConversation:id,contact_name,phone_number',
                'slaEscalations' => fn ($query) => $query->latest('triggered_at'),
            ]),
        ]);
    }

    public function edit(Ticket $ticket): View
    {
        return view('admin.service.tickets.edit', [
            'ticket' => $ticket,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => Ticket::statusOptions(),
            'priorityOptions' => Ticket::priorityOptions(),
            'channelOptions' => Ticket::channelOptions(),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->ticketWorkflowService->update($ticket, $request->ticketData());

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

    protected function conversationName(WhatsAppConversation $conversation): string
    {
        return $conversation->contact_name
            ?: $conversation->customer?->name
            ?: $conversation->phone_number
            ?: 'Customer';
    }

}
