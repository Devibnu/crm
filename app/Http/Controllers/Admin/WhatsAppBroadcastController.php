<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\MarketingCampaign;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WhatsAppBroadcastController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $targetType = trim((string) $request->query('target_type', ''));

        $broadcasts = WhatsAppBroadcast::query()
            ->with('marketingCampaign:id,name')
            ->search($search)
            ->filterStatus($status, $this->statusOptions())
            ->filterTargetType($targetType, $this->targetTypeOptions())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => WhatsAppBroadcast::query()->count(),
            'scheduled' => WhatsAppBroadcast::query()->where('status', 'scheduled')->count(),
            'sending' => WhatsAppBroadcast::query()->where('status', 'sending')->count(),
            'completed' => WhatsAppBroadcast::query()->where('status', 'completed')->count(),
            'total_replies' => WhatsAppBroadcast::query()->sum('replied_count'),
        ];

        return view('admin.marketing.whatsapp-broadcasts.index', [
            'broadcasts' => $broadcasts,
            'search' => $search,
            'selectedStatus' => $status,
            'selectedTargetType' => $targetType,
            'statusOptions' => $this->statusOptions(),
            'targetTypeOptions' => $this->targetTypeOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.marketing.whatsapp-broadcasts.create', [
            'broadcast' => null,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'targetTypeOptions' => $this->targetTypeOptions(),
            'recipientTypeOptions' => $this->recipientTypeOptions(),
            'defaultRecipientType' => 'customer',
            'recipientRows' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $recipientType = $request->string('recipient_type', 'customer')->toString();

        $broadcast = WhatsAppBroadcast::create($data);
        $this->syncRecipients($broadcast, $recipientType);

        return redirect()
            ->route('admin.marketing.whatsapp-broadcasts.show', $broadcast)
            ->with('success', 'WhatsApp broadcast berhasil dibuat.');
    }

    public function show(WhatsAppBroadcast $whatsappBroadcast): View
    {
        $whatsappBroadcast->load(['marketingCampaign:id,name', 'recipients']);

        return view('admin.marketing.whatsapp-broadcasts.show', [
            'broadcast' => $whatsappBroadcast,
            'recipientRows' => $whatsappBroadcast->recipients->take(15),
            'statusTracking' => $this->statusTracking($whatsappBroadcast),
        ]);
    }

    public function edit(WhatsAppBroadcast $whatsappBroadcast): View
    {
        $whatsappBroadcast->load('recipients');

        return view('admin.marketing.whatsapp-broadcasts.edit', [
            'broadcast' => $whatsappBroadcast,
            'campaigns' => MarketingCampaign::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => $this->statusOptions(),
            'targetTypeOptions' => $this->targetTypeOptions(),
            'recipientTypeOptions' => $this->recipientTypeOptions(),
            'defaultRecipientType' => $whatsappBroadcast->recipients->first()?->recipient_type ?? 'customer',
            'recipientRows' => $whatsappBroadcast->recipients,
        ]);
    }

    public function update(Request $request, WhatsAppBroadcast $whatsappBroadcast): RedirectResponse
    {
        $data = $this->validatedData($request);
        $recipientType = $request->string('recipient_type', 'customer')->toString();

        $whatsappBroadcast->update($data);
        $this->syncRecipients($whatsappBroadcast, $recipientType);

        return redirect()
            ->route('admin.marketing.whatsapp-broadcasts.show', $whatsappBroadcast)
            ->with('success', 'WhatsApp broadcast berhasil diperbarui.');
    }

    public function destroy(WhatsAppBroadcast $whatsappBroadcast): RedirectResponse
    {
        $whatsappBroadcast->delete();

        return redirect()
            ->route('admin.marketing.whatsapp-broadcasts.index')
            ->with('success', 'WhatsApp broadcast berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'name' => ['required', 'string', 'max:255'],
            'message_template' => ['required', 'string'],
            'target_type' => ['required', Rule::in($this->targetTypeOptions())],
            'status' => ['required', Rule::in($this->statusOptions())],
            'scheduled_at' => ['nullable', 'date'],
            'sent_at' => ['nullable', 'date', 'after_or_equal:scheduled_at'],
            'created_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'recipient_type' => ['required', Rule::in($this->recipientTypeOptions())],
        ]);

        unset($validated['recipient_type']);
        $validated['marketing_campaign_id'] = $validated['marketing_campaign_id'] ?? null;

        return $validated;
    }

    protected function syncRecipients(WhatsAppBroadcast $broadcast, string $recipientType): void
    {
        $recipientCollection = $recipientType === 'lead'
            ? Lead::query()->whereNotNull('phone')->orderBy('id')->get(['id', 'name', 'phone'])
            : Customer::query()->whereNotNull('phone')->orderBy('id')->get(['id', 'name', 'phone']);

        $rows = $recipientCollection
            ->map(fn ($recipient) => [
                'recipient_type' => $recipientType,
                'recipient_id' => $recipient->id,
                'recipient_name' => $recipient->name,
                'phone_number' => $recipient->phone,
                'status' => 'queued',
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        $broadcast->recipients()->delete();

        if ($rows !== []) {
            WhatsAppBroadcastRecipient::query()->insert(array_map(function (array $row) use ($broadcast) {
                $row['whatsapp_broadcast_id'] = $broadcast->id;

                return $row;
            }, $rows));
        }

        $totals = $broadcast->recipients()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status IN ('sent','delivered','read','replied') THEN 1 ELSE 0 END) as sent_total")
            ->selectRaw("SUM(CASE WHEN status IN ('delivered','read','replied') THEN 1 ELSE 0 END) as delivered_total")
            ->selectRaw("SUM(CASE WHEN status IN ('read','replied') THEN 1 ELSE 0 END) as read_total")
            ->selectRaw("SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_total")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_total")
            ->first();

        $broadcast->update([
            'total_recipients' => (int) ($totals->total ?? 0),
            'sent_count' => (int) ($totals->sent_total ?? 0),
            'delivered_count' => (int) ($totals->delivered_total ?? 0),
            'read_count' => (int) ($totals->read_total ?? 0),
            'replied_count' => (int) ($totals->replied_total ?? 0),
            'failed_count' => (int) ($totals->failed_total ?? 0),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function statusTracking(WhatsAppBroadcast $broadcast): array
    {
        return [
            ['label' => 'Total Recipients', 'value' => $broadcast->total_recipients],
            ['label' => 'Sent', 'value' => $broadcast->sent_count],
            ['label' => 'Delivered', 'value' => $broadcast->delivered_count],
            ['label' => 'Read', 'value' => $broadcast->read_count],
            ['label' => 'Replied', 'value' => $broadcast->replied_count],
            ['label' => 'Failed', 'value' => $broadcast->failed_count],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['draft', 'scheduled', 'sending', 'completed', 'failed', 'cancelled'];
    }

    /**
     * @return array<int, string>
     */
    protected function targetTypeOptions(): array
    {
        return ['segment', 'customer', 'lead'];
    }

    /**
     * @return array<int, string>
     */
    protected function recipientTypeOptions(): array
    {
        return ['customer', 'lead'];
    }
}
