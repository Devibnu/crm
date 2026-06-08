<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppBroadcastJob;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\MarketingCampaign;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            ->with(['marketingCampaign:id,name', 'messageTemplate:id,name,category,language'])
            ->select([
                'id',
                'marketing_campaign_id',
                'name',
                'message_template',
                'target_type',
                'status',
                'scheduled_at',
                'total_recipients',
                'sent_count',
                'total_sent',
                'delivered_count',
                'read_count',
                'replied_count',
                'failed_count',
                'total_failed',
                'delivery_rate',
                'reply_rate',
                'created_by',
                'created_at',
                'updated_at',
            ])
            ->selectRaw('delivered_count as total_delivered')
            ->selectRaw('read_count as total_read')
            ->selectRaw('replied_count as total_replied')
            ->withCount(['recipients', 'replies'])
            ->search($search)
            ->filterStatus($status, $this->statusOptions())
            ->filterTargetType($targetType, $this->targetTypeOptions())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => WhatsAppBroadcast::query()->count(),
            'draft' => WhatsAppBroadcast::query()->where('status', 'draft')->count(),
            'scheduled' => WhatsAppBroadcast::query()->where('status', 'scheduled')->count(),
            'sending' => WhatsAppBroadcast::query()->where('status', 'sending')->count(),
            'completed' => WhatsAppBroadcast::query()->where('status', 'completed')->count(),
            'failed' => WhatsAppBroadcast::query()->where('status', 'failed')->count(),
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
            'approvedTemplates' => $this->approvedTemplates(),
            'audienceOptions' => $this->audienceOptions(),
            'audienceCounts' => $this->audienceCounts(),
            'pricePerMessage' => 350,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $recipientType = $data['_recipient_type'];
        unset($data['_recipient_type'], $data['_audience_key'], $data['_rate_limit']);

        $broadcast = WhatsAppBroadcast::create($data);
        Log::info('WhatsApp broadcast created', [
            'broadcast_id' => $broadcast->id,
            'status' => $broadcast->status,
            'target_type' => $broadcast->target_type,
        ]);

        $this->syncRecipients($broadcast, $recipientType);
        Log::info('WhatsApp broadcast recipients synced', [
            'broadcast_id' => $broadcast->id,
            'total_recipients' => $broadcast->total_recipients,
        ]);

        if ($broadcast->status === 'sending') {
            Log::info('Dispatching queued recipients on broadcast create', [
                'broadcast_id' => $broadcast->id,
                'status' => $broadcast->status,
            ]);
            $this->dispatchQueuedRecipients($broadcast);
        }

        return redirect()
            ->route('admin.marketing.whatsapp-broadcasts.show', $broadcast)
            ->with('success', 'WhatsApp broadcast berhasil dibuat.');
    }

    public function show(WhatsAppBroadcast $whatsappBroadcast): View
    {
        $whatsappBroadcast->load('marketingCampaign:id,name');
        $recipientRows = $whatsappBroadcast->recipients()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
        $statusCounts = $whatsappBroadcast->recipients()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.marketing.whatsapp-broadcasts.show', [
            'broadcast' => $whatsappBroadcast,
            'recipientRows' => $recipientRows,
            'statusTracking' => $this->statusTracking($whatsappBroadcast, $statusCounts),
            'queuedCount' => (int) ($statusCounts['queued'] ?? 0),
            'defaultWhatsAppProvider' => WhatsAppProvider::query()->default()->value('provider'),
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
            'approvedTemplates' => $this->approvedTemplates(),
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

    public function start(WhatsAppBroadcast $whatsappBroadcast): RedirectResponse
    {
        Log::info('Starting WhatsApp broadcast', [
            'broadcast_id' => $whatsappBroadcast->id,
            'current_status' => $whatsappBroadcast->status,
        ]);

        $resetCount = $whatsappBroadcast->recipients()
            ->whereIn('status', ['failed', 'sending'])
            ->update([
                'status' => 'queued',
                'error_message' => null,
                'failed_reason' => null,
            ]);

        Log::info('Reset failed/sending recipients to queued', [
            'broadcast_id' => $whatsappBroadcast->id,
            'reset_count' => $resetCount,
        ]);

        $whatsappBroadcast->update([
            'status' => 'sending',
            'sent_at' => null,
        ]);
        $whatsappBroadcast->refresh();

        Log::info('Broadcast status updated to sending, dispatching queued recipients', [
            'broadcast_id' => $whatsappBroadcast->id,
            'status' => $whatsappBroadcast->status,
        ]);

        $dispatched = $this->dispatchQueuedRecipients($whatsappBroadcast);

        Log::info('Broadcast started', [
            'broadcast_id' => $whatsappBroadcast->id,
            'dispatched_count' => $dispatched,
        ]);

        return redirect()
            ->route('admin.marketing.whatsapp-broadcasts.show', $whatsappBroadcast)
            ->with('success', "WhatsApp broadcast masuk queue pengiriman ({$dispatched} jobs diqueue).");
    }

    public function pause(WhatsAppBroadcast $whatsappBroadcast): RedirectResponse
    {
        $whatsappBroadcast->update(['status' => 'paused']);

        return redirect()
            ->route('admin.marketing.whatsapp-broadcasts.show', $whatsappBroadcast)
            ->with('success', 'WhatsApp broadcast dijeda.');
    }

    public function resume(WhatsAppBroadcast $whatsappBroadcast): RedirectResponse
    {
        Log::info('Resuming WhatsApp broadcast', [
            'broadcast_id' => $whatsappBroadcast->id,
            'current_status' => $whatsappBroadcast->status,
        ]);

        $whatsappBroadcast->update(['status' => 'sending']);
        $whatsappBroadcast->refresh();

        $dispatched = $this->dispatchQueuedRecipients($whatsappBroadcast);

        Log::info('Broadcast resumed', [
            'broadcast_id' => $whatsappBroadcast->id,
            'dispatched_count' => $dispatched,
        ]);

        return redirect()
            ->route('admin.marketing.whatsapp-broadcasts.show', $whatsappBroadcast)
            ->with('success', "WhatsApp broadcast dilanjutkan ({$dispatched} jobs diqueue).");
    }

    public function retryQueue(WhatsAppBroadcast $whatsappBroadcast): RedirectResponse
    {
        Log::info('Retry queue requested for broadcast', [
            'broadcast_id' => $whatsappBroadcast->id,
            'current_status' => $whatsappBroadcast->status,
        ]);

        if (! in_array($whatsappBroadcast->status, ['sending', 'scheduled'], true)) {
            $whatsappBroadcast->update(['status' => 'sending']);
            $whatsappBroadcast->refresh();
            Log::info('Broadcast status updated to sending for retry', [
                'broadcast_id' => $whatsappBroadcast->id,
                'new_status' => $whatsappBroadcast->status,
            ]);
        }

        $dispatched = $this->dispatchQueuedRecipients($whatsappBroadcast);

        Log::info('Retry queue completed', [
            'broadcast_id' => $whatsappBroadcast->id,
            'dispatched' => $dispatched,
        ]);

        return redirect()
            ->route('admin.marketing.whatsapp-broadcasts.show', $whatsappBroadcast)
            ->with('success', "Retry queue berhasil dispatch {$dispatched} recipient.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'name' => ['required', 'string', 'max:255'],
            'message_template' => ['nullable', 'string'],
            'send_mode' => ['nullable', Rule::in(['custom_text', 'meta_template'])],
            'whatsapp_message_template_id' => ['nullable', 'exists:whatsapp_message_templates,id'],
            'template_variable_defaults' => ['nullable', 'array'],
            'target_type' => ['nullable', Rule::in($this->targetTypeOptions())],
            'audience' => ['nullable', Rule::in(array_keys($this->audienceOptions()))],
            'schedule_type' => ['nullable', Rule::in(['draft', 'now', 'scheduled'])],
            'rate_limit' => ['nullable', 'numeric', Rule::in([1, 5, 10, 20])],
            'status' => ['nullable', Rule::in($this->statusOptions())],
            'scheduled_at' => ['nullable', 'date'],
            'sent_at' => ['nullable', 'date', 'after_or_equal:scheduled_at'],
            'created_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'recipient_type' => ['nullable', Rule::in($this->recipientTypeOptions())],
        ]);

        $audience = $validated['audience'] ?? null;
        $audienceMeta = $audience ? ($this->audienceOptions()[$audience] ?? null) : null;
        $recipientType = $validated['recipient_type'] ?? $audienceMeta['recipient_type'] ?? $validated['target_type'] ?? 'customer';
        $recipientType = in_array($recipientType, $this->recipientTypeOptions(), true) ? $recipientType : 'customer';

        unset($validated['recipient_type'], $validated['audience'], $validated['schedule_type'], $validated['rate_limit']);
        $validated['marketing_campaign_id'] = $validated['marketing_campaign_id'] ?? null;
        $validated['send_mode'] = $validated['send_mode'] ?? ($request->filled('whatsapp_message_template_id') ? 'meta_template' : 'custom_text');
        $validated['target_type'] = $recipientType;

        $scheduleType = $request->string('schedule_type', '')->toString();
        if ($scheduleType !== '') {
            $validated['status'] = match ($scheduleType) {
                'draft' => 'draft',
                'scheduled' => 'scheduled',
                default => 'sending',
            };
        } else {
            $validated['status'] = $validated['status'] ?? 'draft';
        }

        if ($validated['status'] === 'scheduled' && empty($validated['scheduled_at'])) {
            abort(422, 'scheduled_at wajib diisi jika Jadwalkan dipilih.');
        }

        if ($validated['send_mode'] === 'meta_template') {
            $template = WhatsAppMessageTemplate::query()
                ->whereHas('provider', fn ($query) => $query->where('provider', 'meta')->where('status', 'active'))
                ->where('status', 'APPROVED')
                ->find($validated['whatsapp_message_template_id'] ?? null);
            if ($template === null) {
                abort(422, 'Template approved wajib dipilih.');
            }
            $validated['whatsapp_message_template_id'] = $template->id;
            $validated['message_template'] = $template->body ?: $template->body_meta ?: '-';
        } else {
            if (trim((string) ($validated['message_template'] ?? '')) === '') {
                abort(422, 'Message template wajib diisi.');
            }
            $validated['whatsapp_message_template_id'] = null;
            $validated['template_variable_defaults'] = null;
        }

        if ($this->validRecipientCount($recipientType) < 1) {
            abort(422, 'Tidak ada penerima valid untuk target ini.');
        }

        $validated['_recipient_type'] = $recipientType;
        $validated['_audience_key'] = $audience;
        $validated['_rate_limit'] = (int) ($request->input('rate_limit', 10));

        return $validated;
    }

    protected function syncRecipients(WhatsAppBroadcast $broadcast, string $recipientType): void
    {
        $recipientCollection = $this->recipientQuery($recipientType)->orderBy('id')->get(['id', 'name', 'phone']);

        $rows = $recipientCollection
            ->map(function ($recipient) use ($recipientType) {
                $phone = $this->normalizeIndonesianPhone($recipient->phone);

                if ($phone === null) {
                    return null;
                }

                return [
                    'recipient_type' => $recipientType,
                    'recipient_id' => $recipient->id,
                    'recipient_name' => $recipient->name,
                    'phone_number' => $phone,
                    'status' => 'queued',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->filter()
            ->all();

        $broadcast->recipients()->delete();

        if ($rows !== []) {
            WhatsAppBroadcastRecipient::query()->insert(array_map(function (array $row) use ($broadcast) {
                $row['whatsapp_broadcast_id'] = $broadcast->id;

                return $row;
            }, $rows));
        }

        $broadcast->refreshDeliveryStats();
    }

    protected function dispatchQueuedRecipients(WhatsAppBroadcast $broadcast): int
    {
        $queuedCount = $broadcast->recipients()->where('status', 'queued')->count();

        Log::info('Dispatching queued recipients for broadcast', [
            'broadcast_id' => $broadcast->id,
            'broadcast_status' => $broadcast->status,
            'total_recipients' => $broadcast->total_recipients,
            'queued_count' => $queuedCount,
        ]);

        $dispatched = 0;
        $failed = [];

        $broadcast->recipients()
            ->where('status', 'queued')
            ->orderBy('id')
            ->get(['id', 'whatsapp_broadcast_id', 'phone_number'])
            ->each(function (WhatsAppBroadcastRecipient $recipient) use ($broadcast, &$dispatched, &$failed) {
                try {
                    Log::info('Dispatching broadcast recipient job', [
                        'broadcast_id' => $broadcast->id,
                        'recipient_id' => $recipient->id,
                        'phone_number' => $recipient->phone_number,
                    ]);

                    SendWhatsAppBroadcastJob::dispatch($broadcast->id, $recipient->id);
                    $dispatched++;
                } catch (\Exception $e) {
                    Log::error('Failed to dispatch recipient job', [
                        'broadcast_id' => $broadcast->id,
                        'recipient_id' => $recipient->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = $recipient->id;
                }
            });

        Log::info('Dispatch job batch completed', [
            'broadcast_id' => $broadcast->id,
            'total_queued' => $queuedCount,
            'dispatched' => $dispatched,
            'failed' => count($failed),
            'queued_jobs_count' => DB::table('jobs')->where('payload', 'like', '%SendWhatsAppBroadcastJob%')->count(),
        ]);

        return $dispatched;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function statusTracking(WhatsAppBroadcast $broadcast, $statusCounts = null): array
    {
        $queued = $statusCounts === null
            ? $broadcast->recipients()->where('status', 'queued')->count()
            : (int) ($statusCounts['queued'] ?? 0);

        return [
            ['label' => 'Total Recipients', 'value' => $broadcast->total_recipients],
            ['label' => 'Queued', 'value' => $queued],
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
        return ['draft', 'scheduled', 'sending', 'paused', 'completed', 'failed', 'cancelled'];
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

    private function approvedTemplates()
    {
        return WhatsAppMessageTemplate::query()
            ->with('provider:id,name,provider,status')
            ->whereHas('provider', fn ($query) => $query->where('provider', 'meta')->where('status', 'active'))
            ->where('status', 'APPROVED')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    private function audienceOptions(): array
    {
        return [
            'all_customers' => ['label' => 'Semua Customer', 'recipient_type' => 'customer'],
            'all_leads' => ['label' => 'Semua Lead', 'recipient_type' => 'lead'],
            'active_customers' => ['label' => 'Customer aktif', 'recipient_type' => 'customer'],
            'active_leads' => ['label' => 'Lead aktif', 'recipient_type' => 'lead'],
            'manual_import' => ['label' => 'Manual upload/import nanti', 'recipient_type' => 'customer', 'disabled' => true],
        ];
    }

    private function audienceCounts(): array
    {
        return [
            'all_customers' => $this->validRecipientCount('customer'),
            'active_customers' => $this->validRecipientCount('customer', true),
            'all_leads' => $this->validRecipientCount('lead'),
            'active_leads' => $this->validRecipientCount('lead', true),
            'manual_import' => 0,
        ];
    }

    private function validRecipientCount(string $recipientType, bool $activeOnly = false): int
    {
        return $this->recipientQuery($recipientType, $activeOnly)
            ->get(['phone'])
            ->filter(fn ($row) => $this->normalizeIndonesianPhone($row->phone) !== null)
            ->count();
    }

    private function recipientQuery(string $recipientType, bool $activeOnly = false)
    {
        $query = $recipientType === 'lead'
            ? Lead::query()->whereNotNull('phone')
            : Customer::query()->whereNotNull('phone');

        if ($activeOnly) {
            $recipientType === 'lead'
                ? $query->whereIn('status', ['new', 'contacted', 'qualified'])
                : $query->where('status', 'active');
        }

        return $query;
    }

    private function normalizeIndonesianPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62') || strlen($digits) < 10) {
            return null;
        }

        return $digits;
    }
}
