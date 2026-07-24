@php
    $satisfaction = $satisfaction ?? null;
    $selectedRating = old('rating', $satisfaction->rating ?? 5);
    $selectedSentiment = old('sentiment', $satisfaction->sentiment ?? 'positive');
    $selectedChannel = old('survey_channel', $satisfaction->survey_channel ?? 'web');
    $selectedFollowUp = old('follow_up_required', isset($satisfaction) ? (int) $satisfaction->follow_up_required : 0);
    $selectedCustomerId = old('customer_id', $satisfaction->customer_id ?? '');
    $selectedTicketId = old('ticket_id', $satisfaction->ticket_id ?? '');
@endphp

<div class="sales-form-sections"
    data-csat-form
    data-ticket-url-template="{{ route('admin.service.customer-satisfaction.customer-tickets', ['customer' => '__CUSTOMER__']) }}">
    <div class="sales-form-section">
        <h2>Survey Context</h2>
        <p>Connect the feedback to a customer, ticket, channel, and submission timestamp.</p>
        <div class="customer-form-grid">
            <label class="field">
                <span>Customer</span>
                <select name="customer_id" data-csat-customer-select>
                    <option value="">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Ticket (Optional)</span>
                <select name="ticket_id"
                    data-csat-ticket-select
                    data-selected-ticket="{{ $selectedTicketId }}"
                    data-label-select-customer="Pilih customer terlebih dahulu"
                    data-label-loading="Memuat ticket..."
                    data-label-none="Tanpa ticket"
                    data-label-empty="Tidak ada ticket terkait"
                    data-label-error="Gagal memuat ticket, silakan coba lagi"
                    disabled>
                    <option value="">Pilih customer terlebih dahulu</option>
                </select>
                @error('ticket_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Survey Channel <strong>*</strong></span>
                <select name="survey_channel" required>
                    @foreach ($channelOptions as $channel)
                        <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst($channel) }}</option>
                    @endforeach
                </select>
                @error('survey_channel')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Submitted At</span>
                <input type="datetime-local" name="submitted_at" value="{{ old('submitted_at', optional($satisfaction->submitted_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('submitted_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Feedback Details</h2>
        <p>Record the customer's rating, sentiment, and feedback narrative.</p>
        <div class="customer-form-grid">
            <label class="field">
                <span>Rating <strong>*</strong></span>
                <select name="rating" required>
                    @foreach ($ratingOptions as $rating)
                        <option value="{{ $rating }}" @selected((string) $selectedRating === (string) $rating)>{{ $rating }}</option>
                    @endforeach
                </select>
                @error('rating')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Sentiment <strong>*</strong></span>
                <select name="sentiment" required>
                    @foreach ($sentimentOptions as $sentiment)
                        <option value="{{ $sentiment }}" @selected($selectedSentiment === $sentiment)>{{ ucfirst($sentiment) }}</option>
                    @endforeach
                </select>
                @error('sentiment')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Feedback</span>
                <textarea name="feedback" rows="5">{{ old('feedback', $satisfaction->feedback ?? '') }}</textarea>
                @error('feedback')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Follow Up</h2>
        <p>Flag feedback that needs action and keep the follow-up note clear for the service team.</p>
        <div class="customer-form-grid">
            <label class="field">
                <span>Follow Up Required <strong>*</strong></span>
                <select name="follow_up_required" required>
                    <option value="0" @selected((string) $selectedFollowUp === '0')>No follow up</option>
                    <option value="1" @selected((string) $selectedFollowUp === '1')>Follow up required</option>
                </select>
                @error('follow_up_required')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Follow Up Notes</span>
                <textarea name="follow_up_notes" rows="4">{{ old('follow_up_notes', $satisfaction->follow_up_notes ?? '') }}</textarea>
                @error('follow_up_notes')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-csat-form]').forEach((form) => {
            const customerSelect = form.querySelector('[data-csat-customer-select]');
            const ticketSelect = form.querySelector('[data-csat-ticket-select]');

            if (!customerSelect || !ticketSelect || !form.dataset.ticketUrlTemplate) return;

            let selectedTicket = ticketSelect.dataset.selectedTicket || '';

            const replaceOptions = (label, disabled = true) => {
                ticketSelect.innerHTML = '';
                ticketSelect.append(new Option(label, ''));
                ticketSelect.disabled = disabled;
            };

            const renderTickets = (tickets) => {
                ticketSelect.innerHTML = '';

                if (!tickets.length) {
                    replaceOptions(ticketSelect.dataset.labelEmpty, true);
                    return;
                }

                ticketSelect.append(new Option(ticketSelect.dataset.labelNone, ''));

                tickets.forEach((ticket) => {
                    const option = new Option(`${ticket.ticket_number} — ${ticket.subject}`, ticket.id);
                    if (String(ticket.id) === String(selectedTicket)) option.selected = true;
                    ticketSelect.append(option);
                });

                ticketSelect.disabled = false;
            };

            const loadTickets = async (customerId) => {
                if (!customerId) {
                    selectedTicket = '';
                    replaceOptions(ticketSelect.dataset.labelSelectCustomer, true);
                    return;
                }

                replaceOptions(ticketSelect.dataset.labelLoading, true);

                try {
                    const response = await fetch(form.dataset.ticketUrlTemplate.replace('__CUSTOMER__', customerId), {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) throw new Error('Unable to load tickets.');

                    const payload = await response.json();
                    renderTickets(payload.data || []);
                } catch (error) {
                    replaceOptions(ticketSelect.dataset.labelError, true);
                }
            };

            customerSelect.addEventListener('change', () => {
                selectedTicket = '';
                loadTickets(customerSelect.value);
            });

            loadTickets(customerSelect.value);
        });
    });
</script>
