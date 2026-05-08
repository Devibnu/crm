<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerSatisfaction;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerSatisfactionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $rating = trim((string) $request->query('rating', ''));
        $sentiment = trim((string) $request->query('sentiment', ''));
        $channel = trim((string) $request->query('survey_channel', ''));
        $followUp = trim((string) $request->query('follow_up_required', ''));

        $feedback = CustomerSatisfaction::query()
            ->with(['customer:id,name', 'ticket:id,ticket_number,subject'])
            ->when($search !== '', fn ($query) => $query->search($search))
            ->filterRating($rating)
            ->filterValue('sentiment', $sentiment, $this->sentimentOptions())
            ->filterValue('survey_channel', $channel, $this->channelOptions())
            ->filterFollowUp($followUp)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $averageRating = (float) CustomerSatisfaction::query()->avg('rating');

        $summary = [
            'total' => CustomerSatisfaction::query()->count(),
            'average_rating' => $averageRating,
            'positive' => CustomerSatisfaction::query()->where('sentiment', 'positive')->count(),
            'follow_up_required' => CustomerSatisfaction::query()->where('follow_up_required', true)->count(),
        ];

        return view('admin.service.customer-satisfaction.index', [
            'feedback' => $feedback,
            'search' => $search,
            'selectedRating' => $rating,
            'selectedSentiment' => $sentiment,
            'selectedChannel' => $channel,
            'selectedFollowUp' => $followUp,
            'ratingOptions' => $this->ratingOptions(),
            'sentimentOptions' => $this->sentimentOptions(),
            'channelOptions' => $this->channelOptions(),
            'followUpOptions' => $this->followUpOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.service.customer-satisfaction.create', [
            'satisfaction' => null,
            'tickets' => Ticket::query()->latest()->get(['id', 'ticket_number', 'subject']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'ratingOptions' => $this->ratingOptions(),
            'sentimentOptions' => $this->sentimentOptions(),
            'channelOptions' => $this->channelOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $satisfaction = CustomerSatisfaction::create($this->validatedData($request));

        return redirect()
            ->route('admin.service.customer-satisfaction.show', $satisfaction)
            ->with('success', 'Customer satisfaction berhasil ditambahkan.');
    }

    public function show(CustomerSatisfaction $customerSatisfaction): View
    {
        return view('admin.service.customer-satisfaction.show', [
            'satisfaction' => $customerSatisfaction->load(['customer:id,name', 'ticket:id,ticket_number,subject']),
        ]);
    }

    public function edit(CustomerSatisfaction $customerSatisfaction): View
    {
        return view('admin.service.customer-satisfaction.edit', [
            'satisfaction' => $customerSatisfaction,
            'tickets' => Ticket::query()->latest()->get(['id', 'ticket_number', 'subject']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'ratingOptions' => $this->ratingOptions(),
            'sentimentOptions' => $this->sentimentOptions(),
            'channelOptions' => $this->channelOptions(),
        ]);
    }

    public function update(Request $request, CustomerSatisfaction $customerSatisfaction): RedirectResponse
    {
        $customerSatisfaction->update($this->validatedData($request));

        return redirect()
            ->route('admin.service.customer-satisfaction.show', $customerSatisfaction)
            ->with('success', 'Customer satisfaction berhasil diperbarui.');
    }

    public function destroy(CustomerSatisfaction $customerSatisfaction): RedirectResponse
    {
        $customerSatisfaction->delete();

        return redirect()
            ->route('admin.service.customer-satisfaction.index')
            ->with('success', 'Customer satisfaction berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'ticket_id' => ['nullable', 'exists:tickets,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string'],
            'survey_channel' => ['required', Rule::in($this->channelOptions())],
            'sentiment' => ['required', Rule::in($this->sentimentOptions())],
            'submitted_at' => ['nullable', 'date'],
            'follow_up_required' => ['required', 'boolean'],
            'follow_up_notes' => ['nullable', 'string'],
        ]);

        $validated['ticket_id'] = $validated['ticket_id'] ?? null;
        $validated['customer_id'] = $validated['customer_id'] ?? null;

        return $validated;
    }

    /**
     * @return array<int, int>
     */
    protected function ratingOptions(): array
    {
        return [1, 2, 3, 4, 5];
    }

    /**
     * @return array<int, string>
     */
    protected function sentimentOptions(): array
    {
        return ['positive', 'neutral', 'negative'];
    }

    /**
     * @return array<int, string>
     */
    protected function channelOptions(): array
    {
        return ['email', 'whatsapp', 'phone', 'web'];
    }

    /**
     * @return array<string, string>
     */
    protected function followUpOptions(): array
    {
        return [
            'yes' => 'Follow up required',
            'no' => 'No follow up',
        ];
    }
}
