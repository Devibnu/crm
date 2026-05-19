<?php

namespace Tests\Feature;

use App\Models\AudienceSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudienceSegmentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_be_opened(): void
    {
        $this->get(route('admin.marketing.audiences.index'))
            ->assertOk()
            ->assertSee('Audience Segmentation')
            ->assertSee('Total Segments')
            ->assertSee('Active Segments')
            ->assertSee('Inactive Segments')
            ->assertSee('Total Estimated Audience');
    }

    public function test_segment_can_be_created(): void
    {
        $response = $this->post(route('admin.marketing.audiences.store'), $this->payload([
            'name' => 'Enterprise Intent Segment',
            'type' => 'behavioral',
        ]));

        $segment = AudienceSegment::query()->where('name', 'Enterprise Intent Segment')->firstOrFail();

        $response->assertRedirect(route('admin.marketing.audiences.show', $segment));
        $this->assertDatabaseHas('audience_segments', [
            'name' => 'Enterprise Intent Segment',
            'type' => 'behavioral',
            'status' => 'active',
        ]);
        $this->assertSame('pricing', $segment->fresh()->criteria['visited_pages'][0]);
    }

    public function test_show_and_edit_pages_can_be_opened(): void
    {
        $segment = AudienceSegment::factory()->create(['name' => 'Show Edit Segment']);

        $this->get(route('admin.marketing.audiences.show', $segment))
            ->assertOk()
            ->assertSee('Show Edit Segment');

        $this->get(route('admin.marketing.audiences.edit', $segment))
            ->assertOk()
            ->assertSee('Edit Audience Segment');
    }

    public function test_segment_can_be_updated(): void
    {
        $segment = AudienceSegment::factory()->create(['name' => 'Before Segment']);

        $response = $this->put(route('admin.marketing.audiences.update', $segment), $this->payload([
            'name' => 'After Segment',
            'type' => 'transactional',
            'status' => 'inactive',
            'criteria' => '{"purchase_count":{">=":3}}',
        ]));

        $response->assertRedirect(route('admin.marketing.audiences.show', $segment));
        $this->assertDatabaseHas('audience_segments', [
            'id' => $segment->id,
            'name' => 'After Segment',
            'type' => 'transactional',
            'status' => 'inactive',
        ]);
        $this->assertSame(3, $segment->fresh()->criteria['purchase_count']['>=']);
    }

    public function test_segment_can_be_deleted(): void
    {
        $segment = AudienceSegment::factory()->create();

        $response = $this->delete(route('admin.marketing.audiences.destroy', $segment));

        $response->assertRedirect(route('admin.marketing.audiences.index'));
        $this->assertDatabaseMissing('audience_segments', ['id' => $segment->id]);
    }

    public function test_search_segment_works(): void
    {
        $match = AudienceSegment::factory()->create([
            'name' => 'Searchable Audience Segment',
            'description' => 'Contains retention targeting rules.',
        ]);
        $other = AudienceSegment::factory()->create(['name' => 'Other Audience Segment']);

        $this->get(route('admin.marketing.audiences.index', ['q' => 'retention targeting']))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee($other->name);
    }

    public function test_filter_type_works(): void
    {
        $demographic = AudienceSegment::factory()->create(['name' => 'Demographic Segment Filter', 'type' => 'demographic']);
        $behavioral = AudienceSegment::factory()->create(['name' => 'Behavioral Segment Filter', 'type' => 'behavioral']);

        $this->get(route('admin.marketing.audiences.index', ['type' => 'demographic']))
            ->assertOk()
            ->assertSee($demographic->name)
            ->assertDontSee($behavioral->name);
    }

    public function test_filter_status_works(): void
    {
        $active = AudienceSegment::factory()->create(['name' => 'Active Segment Filter', 'status' => 'active']);
        $inactive = AudienceSegment::factory()->create(['name' => 'Inactive Segment Filter', 'status' => 'inactive']);

        $this->get(route('admin.marketing.audiences.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name);
    }

    public function test_criteria_json_is_displayed_pretty(): void
    {
        $segment = AudienceSegment::factory()->create([
            'name' => 'Criteria Display Segment',
            'criteria' => [
                'visited_pages' => ['pricing', 'demo'],
                'engagement_score' => ['>=' => 75],
            ],
        ]);

        $this->get(route('admin.marketing.audiences.show', $segment))
            ->assertOk()
            ->assertSee('Criteria JSON')
            ->assertSee('visited_pages')
            ->assertSee('pricing')
            ->assertSee('engagement_score')
            ->assertSee('75');
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Default Audience Segment',
            'type' => 'behavioral',
            'status' => 'active',
            'description' => 'Segment created from feature test.',
            'criteria' => '{"visited_pages":["pricing","demo"],"engagement_score":{">=":70}}',
            'estimated_audience' => 12500,
            'created_by' => 'Marketing Ops',
        ], $overrides);
    }
}
