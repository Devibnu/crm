@php
    $satisfaction = $satisfaction ?? null;
    $selectedRating = old('rating', $satisfaction->rating ?? 5);
    $selectedSentiment = old('sentiment', $satisfaction->sentiment ?? 'positive');
    $selectedChannel = old('survey_channel', $satisfaction->survey_channel ?? 'web');
    $selectedFollowUp = old('follow_up_required', isset($satisfaction) ? (int) $satisfaction->follow_up_required : 0);
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Survey Context</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Customer</span>
                <select name="customer_id">
                    <option value="">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $satisfaction->customer_id ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Ticket</span>
                <select name="ticket_id">
                    <option value="">Tanpa ticket</option>
                    @foreach ($tickets as $ticket)
                        <option value="{{ $ticket->id }}" @selected((string) old('ticket_id', $satisfaction->ticket_id ?? '') === (string) $ticket->id)>
                            {{ $ticket->ticket_number }} - {{ $ticket->subject }}
                        </option>
                    @endforeach
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
