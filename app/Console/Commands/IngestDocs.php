<?php

namespace App\Console\Commands;

use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;

class IngestDocs extends Command
{
    protected $signature = 'docs:ingest {--force : Re-ingest even if articles exist} {--optimize : Use LLM (Groq) to rewrite content into friendly Markdown} {--html : Convert article body to HTML via CommonMark}';
    protected $description = 'Ingest markdown documentation from docs/ into the knowledge base';

    private ?CommonMarkConverter $converter = null;

    private array $sectionMap = [
        '4.1' => 'Contact & Account Management',
        '4.2' => 'Deals & Pipelines',
        '4.3' => 'Omni-Channel Interactions',
        '4.4' => 'Campaigns',
        '4.5' => 'Loyalty & Customer Experience',
        '4.6' => 'Support',
        '4.7' => 'Analytics',
        '4.8' => 'Contracts & Legal',
        '4.9' => 'Finance & Procurement',
        '4.10' => 'Security & Compliance',
        '4.11' => 'Integrations',
        '4.12' => 'Calendar & Notifications',
        '4.13' => 'Documentation Centre',
        '4.14' => 'AI CRM Assistant',
        '4.15' => 'Service & Support',
    ];

    public function handle(): void
    {
        $docsPath = base_path('docs');

        if (!is_dir($docsPath)) {
            $this->error("docs/ directory not found at {$docsPath}");
            return;
        }

        $files = collect(glob($docsPath . '/*.md'))
            ->sort()
            ->values()
            ->all();

        if (empty($files)) {
            $this->warn('No markdown files found in docs/');
            return;
        }

        $author = User::first();
        if (!$author) {
            $this->error('No users found in the database. Create a user first.');
            return;
        }

        $force = $this->option('force');
        $optimize = $this->option('optimize');
        $toHtml = $this->option('html');
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        if ($toHtml) {
            $this->converter = new CommonMarkConverter([
                'allow_unsafe_attributes' => false,
                'allow_unsafe_html' => false,
            ]);
        }

        foreach ($files as $file) {
            $basename = basename($file, '.md');
            $content = file_get_contents($file);

            $section = $this->resolveSection($basename, $content);
            $sectionTitle = $this->sectionMap[$section] ?? Str::title(str_replace('_', ' ', $basename));

            $category = $this->ensureCategory($sectionTitle);

            $featureBlocks = $this->parseFeatureBlocks($content, $section);

            if (empty($featureBlocks)) {
                $this->warn("  No features found in {$basename}.md — ingesting as single article.");

                $slug = Str::slug($sectionTitle);
                $existing = KnowledgeBaseArticle::where('slug', $slug)->first();

                $body = trim($content);
                if ($optimize) {
                    $body = $this->optimizeWithGroq($body, $sectionTitle);
                }
                if ($toHtml) {
                    $body = $this->convertToHtml($body);
                }

                if ($existing) {
                    if ($force) {
                        $existing->update([
                            'title' => $sectionTitle,
                            'body' => $body,
                            'category_id' => $category->id,
                            'author_id' => $author->id,
                            'status' => 'published',
                            'audience' => 'all',
                            'feature_refs' => [$section],
                            'published_at' => $existing->published_at ?? now(),
                        ]);
                        $totalUpdated++;
                        $this->info("  Updated: {$sectionTitle}");
                    } else {
                        $totalSkipped++;
                        $this->line("  Skipped (exists): {$sectionTitle}");
                    }
                } else {
                    KnowledgeBaseArticle::create([
                        'title' => $sectionTitle,
                        'slug' => $slug,
                        'body' => $body,
                        'category_id' => $category->id,
                        'author_id' => $author->id,
                        'status' => 'published',
                        'audience' => 'all',
                        'feature_refs' => [$section],
                        'published_at' => now(),
                    ]);
                    $totalCreated++;
                    $this->info("  Created: {$sectionTitle}");
                }

                continue;
            }

            $featureIndex = 0;
            foreach ($featureBlocks as $featureTitle => $body) {
                $featureIndex++;
                $featureRef = $this->resolveFeatureRef($section, $featureIndex);

                $slug = Str::slug($sectionTitle . ' ' . $featureTitle);
                $existing = KnowledgeBaseArticle::where('slug', $slug)->first();

                if ($optimize) {
                    $body = $this->optimizeWithGroq($body, $featureTitle);
                    usleep(500000);
                }
                if ($toHtml) {
                    $body = $this->convertToHtml($body);
                }

                if ($existing) {
                    if ($force) {
                        $existing->update([
                            'title' => $featureTitle,
                            'body' => $body,
                            'category_id' => $category->id,
                            'author_id' => $author->id,
                            'status' => 'published',
                            'audience' => 'all',
                            'feature_refs' => [$featureRef],
                            'published_at' => $existing->published_at ?? now(),
                        ]);
                        $totalUpdated++;
                        $this->info("  Updated: {$featureTitle}");
                    } else {
                        $totalSkipped++;
                        $this->line("  Skipped (exists): {$featureTitle}");
                    }
                } else {
                    KnowledgeBaseArticle::create([
                        'title' => $featureTitle,
                        'slug' => $slug,
                        'body' => $body,
                        'category_id' => $category->id,
                        'author_id' => $author->id,
                        'status' => 'published',
                        'audience' => 'all',
                        'feature_refs' => [$featureRef],
                        'published_at' => now(),
                    ]);
                    $totalCreated++;
                    $this->info("  Created: {$featureTitle}");
                }
            }
        }

        $this->newLine();
        $this->info("Ingestion complete.");
        $this->line("  Created: {$totalCreated}");
        $this->line("  Updated: {$totalUpdated}");
        $this->line("  Skipped: {$totalSkipped}");
    }

    private function resolveSection(string $basename, string $content): string
    {
        if (preg_match('/^(\d+\.\d+)$/', $basename, $m)) {
            return $m[1];
        }

        if ($basename === 'agent') {
            return '4.14';
        }

        if ($basename === 'serviceman') {
            return '4.15';
        }

        if (preg_match('/^##\s+Section\s+(\d+\.\d+)/i', $content, $m)) {
            return $m[1];
        }

        return $basename;
    }

    private function resolveFeatureRef(string $section, int $index): string
    {
        $parts = explode('.', $section);
        if (count($parts) === 2) {
            return $section . '.' . $index;
        }

        return $section . '-' . $index;
    }

    private function parseFeatureBlocks(string $content, string $section): array
    {
        $blocks = [];

        $lines = explode("\n", $content);
        $currentHeading = null;
        $currentBody = [];

        foreach ($lines as $line) {
            if (preg_match('/^##\s+Feature\s+\d+:\s*(.+)$/i', $line, $m)) {
                if ($currentHeading !== null) {
                    $blocks[$currentHeading] = trim(implode("\n", $currentBody));
                }
                $currentHeading = trim($m[1]);
                $currentBody = [];
                continue;
            }

            if ($currentHeading !== null) {
                $currentBody[] = $line;
            }
        }

        if ($currentHeading !== null) {
            $blocks[$currentHeading] = trim(implode("\n", $currentBody));
        }

        return $blocks;
    }

    private function ensureCategory(string $name): KnowledgeBaseCategory
    {
        $slug = Str::slug($name);

        $category = KnowledgeBaseCategory::where('slug', $slug)->first();

        if ($category) {
            return $category;
        }

        return KnowledgeBaseCategory::create([
            'name' => $name,
            'slug' => $slug,
            'description' => "Auto-created during docs ingestion for {$name}",
            'sort_order' => 0,
        ]);
    }

    private function optimizeWithGroq(string $body, string $featureTitle): string
    {
        $apiKey = env('GROQ_API_KEY');
        $provider = env('LLM_PROVIDER', 'groq');

        if (empty($apiKey) || $provider !== 'groq') {
            return $body;
        }

        $systemPrompt = <<<PROMPT
Rewrite the following feature specification into a user-friendly knowledge base article for CRM agents and managers.

Rules:
- Remove all spec section references (e.g. "4.1.4", "Section 4.1", "Feature 1")
- Use clear, conversational language suitable for end-users
- Preserve every functional detail, permission, and behavior described
- Format with Markdown: headings (##, ###), bullet lists (-), numbered lists (1.), bold (**text**) for key actions/terms, italic (*text*) for warnings/notes
- Start with a short friendly intro (1-2 sentences)
- Group related acceptance criteria into logical sections with descriptive headings
- Do NOT invent features not mentioned in the text
- Output ONLY the article body (no code fences, no YAML frontmatter)
PROMPT;

        $userPrompt = "Feature: {$featureTitle}\n\nSpec text:\n{$body}";

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => env('GROQ_MODEL', 'llama3-70b-8192'),
                    'temperature' => 0.4,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            $content = $response->json('choices.0.message.content');
            if ($response->successful() && null !== $content) {
                $optimized = $content;
                $optimized = $this->stripSpecReferences($optimized);
                return trim($optimized);
            }

            Log::warning('Groq optimization failed for feature', [
                'feature' => $featureTitle,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Groq optimization exception for feature', [
                'feature' => $featureTitle,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->stripSpecReferences($body);
    }

    private function stripSpecReferences(string $text): string
    {
        $patterns = [
            '/References?:\s*[\d.]+\s*/i',
            '/Refs?:\s*[\d.]+\s*/i',
            '/Section\s+[\d.]+\s*/i',
            '/Feature\s+\d+:\s*/i',
            '/\b\d+\.\d+\.\d+\b/',
            '/\b\d+\.\d+\b/',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }

        return trim(preg_replace('/\n{3,}/', "\n\n", $text));
    }

    private function convertToHtml(string $markdown): string
    {
        if ($this->converter === null) {
            $this->converter = new CommonMarkConverter([
                'allow_unsafe_attributes' => false,
                'allow_unsafe_html' => false,
            ]);
        }

        return $this->converter->convert($markdown)->getContent();
    }
}
