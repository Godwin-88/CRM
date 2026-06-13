<?php

namespace App\Services;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseArticleVersion;
use App\Models\User;

class KnowledgeBaseService
{
    public function createVersion(KnowledgeBaseArticle $article, User $author): KnowledgeBaseArticleVersion
    {
        $latestVersion = $article->versions()->max('version_number') ?? 0;

        return $article->versions()->create([
            'version_number' => $latestVersion + 1,
            'title' => $article->title,
            'body' => $article->body,
            'author_id' => $author->id,
        ]);
    }

    public function restoreVersion(KnowledgeBaseArticle $article, KnowledgeBaseArticleVersion $version, User $user): void
    {
        // Create new draft from restored content
        $article->update([
            'title' => $version->title,
            'body' => $version->body,
            'status' => 'draft',
            'published_at' => null,
        ]);

        // Record the restoration as a version
        $this->createVersion($article, $user);
    }
}
