<?php

namespace App\Console\Commands;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;

class BackfillArticleHtml extends Command
{
    protected $signature = 'kb:backfill-html {--force : Update even if body already looks like HTML}';
    protected $description = 'Backfill KnowledgeBaseArticle.body from Markdown to HTML using CommonMark';

    public function handle(): void
    {
        $force = $this->option('force');
        $converter = new CommonMarkConverter([
            'allow_unsafe_attributes' => false,
            'allow_unsafe_html' => false,
        ]);

        $query = KnowledgeBaseArticle::query();

        if (!$force) {
            $query->where(function ($q) {
                $q->where('body', 'not like', '<%')
                  ->orWhere('body', 'like', "\n%\n");
            });
        }

        $articles = $query->orderBy('id')->get();
        $count = 0;
        $skipped = 0;

        foreach ($articles as $article) {
            $body = $article->body;

            if (!$force && preg_match('/^\s*<\w/', $body)) {
                $skipped++;
                $this->line("  Skipped (already HTML): {$article->title}");
                continue;
            }

            $html = $converter->convert($body)->getContent();

            $article->update(['body' => $html]);
            $count++;
            $this->info("  Converted: {$article->title}");
        }

        $this->newLine();
        $this->info("Backfill complete.");
        $this->line("  Converted: {$count}");
        $this->line("  Skipped: {$skipped}");
    }
}
