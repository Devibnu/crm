@php
    $post = $post ?? null;
    $selectedPlatform = old('platform', $post->platform ?? 'instagram');
    $selectedStatus = old('status', $post->status ?? 'draft');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Post Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Campaign</span>
                <select name="marketing_campaign_id">
                    <option value="">Tanpa campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('marketing_campaign_id', $post->marketing_campaign_id ?? '') === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
                @error('marketing_campaign_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Platform <strong>*</strong></span>
                <select name="platform" required>
                    @foreach ($platformOptions as $platform)
                        <option value="{{ $platform }}" @selected($selectedPlatform === $platform)>{{ ucfirst($platform) }}</option>
                    @endforeach
                </select>
                @error('platform')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Post Title <strong>*</strong></span>
                <input type="text" name="post_title" value="{{ old('post_title', $post->post_title ?? '') }}" maxlength="255" placeholder="Launch announcement post" required>
                @error('post_title')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Status <strong>*</strong></span>
                <select name="status" required>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $post->created_by ?? '') }}" maxlength="255" placeholder="Social Media Team">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Content</h2>
        <label class="field">
            <span>Content</span>
            <textarea name="content" rows="5" placeholder="Social caption, post copy, or content notes.">{{ old('content', $post->content ?? '') }}</textarea>
            @error('content')<small class="error">{{ $message }}</small>@enderror
        </label>

        <label class="field">
            <span>Post URL</span>
            <input type="url" name="post_url" value="{{ old('post_url', $post->post_url ?? '') }}" placeholder="https://example.com/post">
            @error('post_url')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2>Engagement Metrics</h2>
        <div class="customer-form-grid">
            @foreach (['likes_count' => 'Likes', 'comments_count' => 'Comments', 'shares_count' => 'Shares', 'impressions_count' => 'Impressions'] as $field => $label)
                <label class="field">
                    <span>{{ $label }}</span>
                    <input type="number" name="{{ $field }}" value="{{ old($field, $post->{$field} ?? 0) }}" min="0" step="1">
                    @error($field)<small class="error">{{ $message }}</small>@enderror
                </label>
            @endforeach

            <label class="field">
                <span>Engagement Rate</span>
                <input type="number" name="engagement_rate" value="{{ old('engagement_rate', $post->engagement_rate ?? 0) }}" min="0" max="100" step="0.01">
                @error('engagement_rate')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Publishing</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Posted At</span>
                <input type="datetime-local" name="posted_at" value="{{ old('posted_at', optional($post->posted_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('posted_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>
</div>
