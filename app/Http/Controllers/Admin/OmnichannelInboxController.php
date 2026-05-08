<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OmnichannelMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OmnichannelInboxController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $channel = trim((string) $request->query('channel', ''));
        $status = trim((string) $request->query('status', ''));

        $messages = OmnichannelMessage::query()
            ->with('customer:id,name')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->filterChannel($channel, $this->channelOptions())
            ->filterStatus($status, $this->statusOptions())
            ->latest('received_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => OmnichannelMessage::query()->count(),
            'unread' => OmnichannelMessage::query()->where('status', 'unread')->count(),
            'pending' => OmnichannelMessage::query()->where('status', 'pending')->count(),
            'resolved' => OmnichannelMessage::query()->where('status', 'resolved')->count(),
        ];

        return view('admin.service.omnichannel.index', [
            'messages' => $messages,
            'search' => $search,
            'selectedChannel' => $channel,
            'selectedStatus' => $status,
            'channelOptions' => $this->channelOptions(),
            'statusOptions' => $this->statusOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.service.omnichannel.create', [
            'message' => null,
            'customers' => $this->customers(),
            'channelOptions' => $this->channelOptions(),
            'directionOptions' => $this->directionOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $message = OmnichannelMessage::create($this->validatedData($request));

        return redirect()
            ->route('admin.service.omnichannel.show', $message)
            ->with('success', 'Omnichannel message berhasil ditambahkan.');
    }

    public function show(OmnichannelMessage $omnichannel): View
    {
        return view('admin.service.omnichannel.show', [
            'message' => $omnichannel->load('customer:id,name'),
        ]);
    }

    public function edit(OmnichannelMessage $omnichannel): View
    {
        return view('admin.service.omnichannel.edit', [
            'message' => $omnichannel,
            'customers' => $this->customers(),
            'channelOptions' => $this->channelOptions(),
            'directionOptions' => $this->directionOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, OmnichannelMessage $omnichannel): RedirectResponse
    {
        $omnichannel->update($this->validatedData($request));

        return redirect()
            ->route('admin.service.omnichannel.show', $omnichannel)
            ->with('success', 'Omnichannel message berhasil diperbarui.');
    }

    public function destroy(OmnichannelMessage $omnichannel): RedirectResponse
    {
        $omnichannel->delete();

        return redirect()
            ->route('admin.service.omnichannel.index')
            ->with('success', 'Omnichannel message berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'channel' => ['required', Rule::in($this->channelOptions())],
            'direction' => ['required', Rule::in($this->directionOptions())],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_contact' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'received_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function channelOptions(): array
    {
        return ['whatsapp', 'email', 'livechat', 'facebook', 'instagram', 'telegram'];
    }

    /**
     * @return array<int, string>
     */
    protected function directionOptions(): array
    {
        return ['inbound', 'outbound'];
    }

    /**
     * @return array<int, string>
     */
    protected function statusOptions(): array
    {
        return ['unread', 'read', 'pending', 'resolved'];
    }

    protected function customers()
    {
        return Customer::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
