@php
    $satisfaction = $satisfaction ?? null;
    $selectedRating = old('rating', $satisfaction->rating ?? 5);
    $selectedSentiment = old('sentiment', $satisfaction->sentiment ?? 'positive');
    $selectedChannel = old('survey_channel', $satisfaction->survey_channel ?? 'web');
    $selectedFollowUp = old('follow_up_required', isset($satisfaction) ? (int) $satisfaction->follow_up_required : 0);
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2 data-lang-en="Survey Context" data-lang-id="Konteks Survei">Survey Context</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Customer" data-lang-id="Customer">Customer</span>
                <select name="customer_id">
                    <option value="" data-lang-en="No customer" data-lang-id="Tanpa customer">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $satisfaction->customer_id ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Ticket" data-lang-id="Tiket">Ticket</span>
                <select name="ticket_id">
                    <option value="" data-lang-en="No ticket" data-lang-id="Tanpa tiket">Tanpa ticket</option>
                    @foreach ($tickets as $ticket)
                        <option value="{{ $ticket->id }}" @selected((string) old('ticket_id', $satisfaction->ticket_id ?? '') === (string) $ticket->id)>
                            {{ $ticket->ticket_number }} - {{ $ticket->subject }}
                        </option>
                    @endforeach
                </select>
                @error('ticket_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Survey Channel" data-lang-id="Channel Survei">Survey Channel</span> <strong>*</strong>
                <select name="survey_channel" required>
                    @foreach ($channelOptions as $channel)
                        <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst($channel) }}</option>
                    @endforeach
                </select>
                @error('survey_channel')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Submitted At" data-lang-id="Dikirim Pada">Submitted At</span>
                <input type="datetime-local" name="submitted_at" value="{{ old('submitted_at', optional($satisfaction->submitted_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('submitted_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Feedback Details" data-lang-id="Detail Feedback">Feedback Details</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Rating" data-lang-id="Rating">Rating</span> <strong>*</strong>
                <select name="rating" required>
                    @foreach ($ratingOptions as $rating)
                        <option value="{{ $rating }}" @selected((string) $selectedRating === (string) $rating)>{{ $rating }}</option>
                    @endforeach
                </select>
                @error('rating')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Sentiment" data-lang-id="Sentimen">Sentiment</span> <strong>*</strong>
                <select name="sentiment" required>
                    @foreach ($sentimentOptions as $sentiment)
                        <option value="{{ $sentiment }}" @selected($selectedSentiment === $sentiment)>{{ ucfirst($sentiment) }}</option>
                    @endforeach
                </select>
                @error('sentiment')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span data-lang-en="Feedback" data-lang-id="Feedback">Feedback</span>
                <textarea name="feedback" rows="5">{{ old('feedback', $satisfaction->feedback ?? '') }}</textarea>
                @error('feedback')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Follow Up" data-lang-id="Tindak Lanjut">Follow Up</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Follow Up Required" data-lang-id="Perlu Tindak Lanjut">Follow Up Required</span> <strong>*</strong>
                <select name="follow_up_required" required>
                    <option value="0" @selected((string) $selectedFollowUp === '0') data-lang-en="No follow up" data-lang-id="Tidak perlu follow up">No follow up</option>
                    <option value="1" @selected((string) $selectedFollowUp === '1') data-lang-en="Follow up required" data-lang-id="Perlu follow up">Follow up required</option>
                </select>
                @error('follow_up_required')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span data-lang-en="Follow Up Notes" data-lang-id="Catatan Tindak Lanjut">Follow Up Notes</span>
                <textarea name="follow_up_notes" rows="4">{{ old('follow_up_notes', $satisfaction->follow_up_notes ?? '') }}</textarea>
                @error('follow_up_notes')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>
</div>
