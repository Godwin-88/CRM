<?php

namespace Tests\Feature;

use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_docs_index_works()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $category = KnowledgeBaseCategory::create([
            'name' => 'General',
            'slug' => 'general',
        ]);

        $category->articles()->create([
            'title' => 'Test Article',
            'slug' => 'test-article',
            'body' => 'This is a test article.',
            'author_id' => $user->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($user)->get('/docs');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Docs/Index')
            ->has('categories', 1)
            ->where('categories.0.articles_count', 1)
        );
    }
}
