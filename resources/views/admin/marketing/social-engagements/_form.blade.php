@php
    $post = $post ?? null;
    $selectedPlatform = old('platform', $post->platform ?? 'instagram');
    $selectedStatus = old('status', $post->status ?? 'draft');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2 data-lang-en="Post Information" data-lang-id="Informasi Post">Post Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Campaign" data-lang-id="Campaign">Campaign</span>
                <select name="marketing_campaign_id">
                    <option value="" data-lang-en="No campaign" data-lang-id="Tanpa campaign">Tanpa campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('marketing_campaign_id', $post->marketing_campaign_id ?? '') === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
                @error('marketing_campaign_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Platform" data-lang-id="Platform">Platform</span> <strong>*</strong></span>
                <select name="platform" required>
                    @foreach ($platformOptions as $platform)
                        <option value="{{ $platform }}" @selected($selectedPlatform === $platform)>{{ ucfirst($platform) }}</option>
                    @endforeach
                </select>
                @error('platform')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Post Title" data-lang-id="Judul Post">Post Title</span> <strong>*</strong></span>
                <input type="text" name="post_title" value="{{ old('post_title', $post->post_title ?? '') }}" maxlength="255" placeholder="Launch announcement post" data-placeholder-en="Launch announcement post" data-placeholder-id="Post pengumuman peluncuran" required>
                @error('post_title')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Status" data-lang-id="Status">Status</span> <strong>*</strong></span>
                <select name="status" required>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $post->created_by ?? '') }}" maxlength="255" placeholder="Social Media Team" data-placeholder-en="Social Media Team" data-placeholder-id="Tim Social Media">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Content" data-lang-id="Konten">Content</h2>
        <label class="field">
            <span data-lang-en="Content" data-lang-id="Konten">Content</span>
            <textarea name="content" rows="5" placeholder="Social caption, post copy, or content notes." data-placeholder-en="Social caption, post copy, or content notes." data-placeholder-id="Caption sosial, copy post, atau catatan konten.">{{ old('content', $post->content ?? '') }}</textarea>
            @error('content')<small class="error">{{ $message }}</small>@enderror
        </label>

        <label class="field">
            <span data-lang-en="Post URL" data-lang-id="URL Post">Post URL</span>
            <input type="url" name="post_url" value="{{ old('post_url', $post->post_url ?? '') }}" placeholder="https://example.com/post" data-placeholder-en="https://example.com/post" data-placeholder-id="https://contoh.com/post">
            @error('post_url')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Engagement Metrics" data-lang-id="Metrik Engagement">Engagement Metrics</h2>
        <div class="customer-form-grid">
            @foreach ([
                'likes_count' => ['en' => 'Likes', 'id' => 'Likes'],
                'comments_count' => ['en' => 'Comments', 'id' => 'Komentar'],
                'shares_count' => ['en' => 'Shares', 'id' => 'Share'],
                'impressions_count' => ['en' => 'Impressions', 'id' => 'Impression'],
            ] as $field => $label)
                <label class="field">
                    <span data-lang-en="{{ $label['en'] }}" data-lang-id="{{ $label['id'] }}">{{ $label['en'] }}</span>
                    <input type="number" name="{{ $field }}" value="{{ old($field, $post->{$field} ?? 0) }}" min="0" step="1">
                    @error($field)<small class="error">{{ $message }}</small>@enderror
                </label>
            @endforeach

            <label class="field">
                <span data-lang-en="Engagement Rate" data-lang-id="Engagement Rate">Engagement Rate</span>
                <input type="number" name="engagement_rate" value="{{ old('engagement_rate', $post->engagement_rate ?? 0) }}" min="0" max="100" step="0.01">
                @error('engagement_rate')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Publishing" data-lang-id="Publikasi">Publishing</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Posted At" data-lang-id="Diposting Pada">Posted At</span>
                <input type="datetime-local" name="posted_at" value="{{ old('posted_at', optional($post->posted_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('posted_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>
</div>
