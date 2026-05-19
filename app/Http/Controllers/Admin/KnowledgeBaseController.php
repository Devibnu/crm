<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $visibility = trim((string) $request->query('visibility', ''));
        $published = trim((string) $request->query('published', ''));

        $articles = KnowledgeBase::query()
            ->when($search !== '', fn ($query) => $query->search($search))
            ->filterCategory($category)
            ->filterVisibility($visibility)
            ->when($published === 'published', fn ($query) => $query->where('is_published', true))
            ->when($published === 'draft', fn ($query) => $query->where('is_published', false))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => KnowledgeBase::query()->count(),
            'published' => KnowledgeBase::query()->where('is_published', true)->count(),
            'draft' => KnowledgeBase::query()->where('is_published', false)->count(),
            'public' => KnowledgeBase::query()->where('visibility', 'public')->count(),
        ];

        return view('admin.service.knowledge-base.index', [
            'articles' => $articles,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedVisibility' => $visibility,
            'selectedPublished' => $published,
            'categories' => KnowledgeBase::query()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->orderBy('category')
                ->distinct()
                ->pluck('category'),
            'visibilityOptions' => $this->visibilityOptions(),
            'publishedOptions' => $this->publishedOptions(),
            'summary' => $summary,
        ]);
    }

    public function create(): View
    {
        return view('admin.service.knowledge-base.create', [
            'article' => null,
            'visibilityOptions' => $this->visibilityOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = KnowledgeBase::create($this->validatedData($request));

        return redirect()
            ->route('admin.service.knowledge-base.show', $article)
            ->with('success', 'Knowledge base article berhasil ditambahkan.');
    }

    public function show(KnowledgeBase $knowledgeBase): View
    {
        $knowledgeBase->increment('views_count');
        $knowledgeBase->refresh();

        return view('admin.service.knowledge-base.show', [
            'article' => $knowledgeBase,
        ]);
    }

    public function edit(KnowledgeBase $knowledgeBase): View
    {
        return view('admin.service.knowledge-base.edit', [
            'article' => $knowledgeBase,
            'visibilityOptions' => $this->visibilityOptions(),
        ]);
    }

    public function update(Request $request, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $knowledgeBase->update($this->validatedData($request, $knowledgeBase));

        return redirect()
            ->route('admin.service.knowledge-base.show', $knowledgeBase)
            ->with('success', 'Knowledge base article berhasil diperbarui.');
    }

    public function destroy(KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $knowledgeBase->delete();

        return redirect()
            ->route('admin.service.knowledge-base.index')
            ->with('success', 'Knowledge base article berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedData(Request $request, ?KnowledgeBase $article = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('knowledge_bases', 'slug')->ignore($article?->id),
            ],
            'category' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', Rule::in($this->visibilityOptions())],
            'content' => ['required', 'string'],
            'tags' => ['nullable', 'string', 'max:255'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['category'] = $validated['category'] ?? null;
        $validated['tags'] = $validated['tags'] ?? null;
        $validated['author_name'] = $validated['author_name'] ?? null;
        $validated['is_published'] = (bool) ($validated['is_published'] ?? false);

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    protected function visibilityOptions(): array
    {
        return ['public', 'internal'];
    }

    /**
     * @return array<string, string>
     */
    protected function publishedOptions(): array
    {
        return [
            'published' => 'Published',
            'draft' => 'Draft',
        ];
    }
}
