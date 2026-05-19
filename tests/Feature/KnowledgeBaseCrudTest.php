<?php

namespace Tests\Feature;

use App\Models\KnowledgeBase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_knowledge_base_index_is_accessible(): void
    {
        $this->get(route('admin.service.knowledge-base.index'))
            ->assertOk()
            ->assertSee('Knowledge Base')
            ->assertSee('Pusat artikel bantuan, FAQ, dan self-service customer.');
    }

    public function test_knowledge_base_article_can_be_created(): void
    {
        $response = $this->post(route('admin.service.knowledge-base.store'), [
            'title' => 'How to reset account password',
            'slug' => 'how-to-reset-account-password',
            'category' => 'FAQ',
            'visibility' => 'public',
            'content' => 'Open the reset page and follow the instructions.',
            'tags' => 'faq, password, login',
            'author_name' => 'Support Writer',
            'published_at' => '2026-05-14T09:00',
            'is_published' => 1,
        ]);

        $article = KnowledgeBase::query()->where('slug', 'how-to-reset-account-password')->firstOrFail();

        $response->assertRedirect(route('admin.service.knowledge-base.show', $article));

        $this->assertDatabaseHas('knowledge_bases', [
            'id' => $article->id,
            'title' => 'How to reset account password',
            'slug' => 'how-to-reset-account-password',
            'category' => 'FAQ',
            'visibility' => 'public',
            'is_published' => true,
        ]);
    }

    public function test_knowledge_base_show_and_edit_pages_are_accessible(): void
    {
        $article = KnowledgeBase::factory()->create([
            'title' => 'Accessible Article',
            'views_count' => 0,
        ]);

        $this->get(route('admin.service.knowledge-base.show', $article))
            ->assertOk()
            ->assertSee('Accessible Article');

        $this->get(route('admin.service.knowledge-base.edit', $article))
            ->assertOk()
            ->assertSee('Edit Knowledge Base Article');
    }

    public function test_knowledge_base_article_can_be_updated(): void
    {
        $article = KnowledgeBase::factory()->create([
            'title' => 'Before KB Update',
            'slug' => 'before-kb-update',
            'visibility' => 'internal',
            'is_published' => false,
        ]);

        $response = $this->put(route('admin.service.knowledge-base.update', $article), [
            'title' => 'After KB Update',
            'slug' => 'after-kb-update',
            'category' => 'Troubleshooting',
            'visibility' => 'public',
            'content' => 'Updated troubleshooting content.',
            'tags' => 'troubleshooting, support',
            'author_name' => 'Updated Author',
            'published_at' => '2026-05-15T10:00',
            'is_published' => 1,
        ]);

        $response->assertRedirect(route('admin.service.knowledge-base.show', $article));

        $this->assertDatabaseHas('knowledge_bases', [
            'id' => $article->id,
            'title' => 'After KB Update',
            'slug' => 'after-kb-update',
            'category' => 'Troubleshooting',
            'visibility' => 'public',
            'is_published' => true,
        ]);
    }

    public function test_knowledge_base_article_can_be_deleted(): void
    {
        $article = KnowledgeBase::factory()->create();

        $response = $this->delete(route('admin.service.knowledge-base.destroy', $article));

        $response->assertRedirect(route('admin.service.knowledge-base.index'));

        $this->assertDatabaseMissing('knowledge_bases', [
            'id' => $article->id,
        ]);
    }

    public function test_knowledge_base_search_works(): void
    {
        $match = KnowledgeBase::factory()->create([
            'title' => 'Searchable Knowledge Article',
            'content' => 'Searchable article content.',
            'tags' => 'searchable, faq',
        ]);
        $other = KnowledgeBase::factory()->create([
            'title' => 'Other Knowledge Article',
            'content' => 'Other content.',
            'tags' => 'other',
        ]);

        $this->get(route('admin.service.knowledge-base.index', ['q' => 'Searchable']))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee($other->title);
    }

    public function test_knowledge_base_category_filter_works(): void
    {
        $faq = KnowledgeBase::factory()->create(['title' => 'FAQ Category Filter', 'category' => 'FAQ']);
        $support = KnowledgeBase::factory()->create(['title' => 'Support Category Filter', 'category' => 'Support Articles']);

        $this->get(route('admin.service.knowledge-base.index', ['category' => 'FAQ']))
            ->assertOk()
            ->assertSee($faq->title)
            ->assertDontSee($support->title);
    }

    public function test_knowledge_base_visibility_filter_works(): void
    {
        $public = KnowledgeBase::factory()->create(['title' => 'Public Visibility Filter', 'visibility' => 'public']);
        $internal = KnowledgeBase::factory()->create(['title' => 'Internal Visibility Filter', 'visibility' => 'internal']);

        $this->get(route('admin.service.knowledge-base.index', ['visibility' => 'public']))
            ->assertOk()
            ->assertSee($public->title)
            ->assertDontSee($internal->title);
    }

    public function test_knowledge_base_views_count_increments_on_show(): void
    {
        $article = KnowledgeBase::factory()->create([
            'views_count' => 7,
        ]);

        $this->get(route('admin.service.knowledge-base.show', $article))
            ->assertOk();

        $this->assertDatabaseHas('knowledge_bases', [
            'id' => $article->id,
            'views_count' => 8,
        ]);
    }
}
