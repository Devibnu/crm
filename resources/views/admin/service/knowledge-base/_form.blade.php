@php
    $article = $article ?? null;
    $selectedVisibility = old('visibility', $article->visibility ?? 'public');
    $selectedPublished = old('is_published', isset($article) ? (int) $article->is_published : 0);
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Article Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Title <strong>*</strong></span>
                <input type="text" name="title" value="{{ old('title', $article->title ?? '') }}" maxlength="255" required>
                @error('title')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Slug <strong>*</strong></span>
                <input type="text" name="slug" value="{{ old('slug', $article->slug ?? '') }}" maxlength="255" required>
                @error('slug')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Category</span>
                <input type="text" name="category" value="{{ old('category', $article->category ?? '') }}" maxlength="255">
                @error('category')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Tags</span>
                <input type="text" name="tags" value="{{ old('tags', $article->tags ?? '') }}" maxlength="255" placeholder="faq, billing, onboarding">
                @error('tags')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Author Name</span>
                <input type="text" name="author_name" value="{{ old('author_name', $article->author_name ?? '') }}" maxlength="255">
                @error('author_name')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Content</h2>
        <label class="field">
            <span>Content <strong>*</strong></span>
            <textarea name="content" rows="12" required>{{ old('content', $article->content ?? '') }}</textarea>
            @error('content')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2>Publishing</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Visibility <strong>*</strong></span>
                <select name="visibility" required>
                    @foreach ($visibilityOptions as $visibility)
                        <option value="{{ $visibility }}" @selected($selectedVisibility === $visibility)>{{ ucfirst($visibility) }}</option>
                    @endforeach
                </select>
                @error('visibility')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Published At</span>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($article->published_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('published_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Published Status</span>
                <select name="is_published">
                    <option value="0" @selected((string) $selectedPublished === '0')>Draft</option>
                    <option value="1" @selected((string) $selectedPublished === '1')>Published</option>
                </select>
                @error('is_published')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>
</div>
