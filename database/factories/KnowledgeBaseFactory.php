<?php

namespace Database\Factories;

use App\Models\KnowledgeBase;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeBase>
 */
class KnowledgeBaseFactory extends Factory
{
    protected $model = KnowledgeBase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement(['FAQ', 'Troubleshooting', 'Onboarding', 'Support Articles']);
        $title = $category.' - '.fake()->unique()->sentence(5);
        $published = fake()->boolean(75);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'category' => $category,
            'visibility' => fake()->randomElement(['public', 'internal']),
            'content' => fake()->paragraphs(4, true),
            'tags' => implode(', ', fake()->randomElements(['faq', 'support', 'billing', 'login', 'onboarding', 'ticket', 'troubleshooting'], 3)),
            'author_name' => fake()->optional()->name(),
            'published_at' => $published ? fake()->dateTimeBetween('-6 months', 'now') : null,
            'is_published' => $published,
            'views_count' => fake()->numberBetween(0, 500),
        ];
    }
}
