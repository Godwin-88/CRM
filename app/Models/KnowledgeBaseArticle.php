<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class KnowledgeBaseArticle extends Model implements HasMedia
{
    use HasFactory, HasUlids, InteractsWithMedia, LogsActivity, Searchable, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    protected $fillable = [
        'title',
        'slug',
        'body',
        'category_id',
        'author_id',
        'status',
        'view_count',
        'helpful_votes',
        'not_helpful_votes',
        'published_at',
        'audience',
        'feature_refs',
        'last_verified_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'feature_refs' => 'array',
    ];

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'body' => strip_tags($this->body),
            'category' => $this->category?->name,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleVersion::class, 'article_id');
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'article_ticket_links')
            ->withTimestamps();
    }

    public function getHelpfulRatio(): float
    {
        $total = $this->helpful_votes + $this->not_helpful_votes;
        if ($total === 0) {
            return 0;
        }

        return round(($this->helpful_votes / $total) * 100, 2);
    }

    public function needsReview(): bool
    {
        $total = $this->helpful_votes + $this->not_helpful_votes;
        if ($total < 10) {
            return false;
        }

        return $this->getHelpfulRatio() < 40;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForAudience($query, string $audience)
    {
        return $query->where(function ($q) use ($audience) {
            $q->where('audience', 'all')
              ->orWhere('audience', $audience)
              ->orWhereHas('author', function ($q) use ($audience) {
                  $q->whereHas('roles', function ($q) use ($audience) {
                      $q->where('name', $audience);
                  });
              });
        });
    }

    public function isStale(): bool
    {
        if (!$this->last_verified_at) {
            return true;
        }

        return $this->last_verified_at->addMonths(6)->isPast();
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function recordHelpfulVote(bool $helpful = true): void
    {
        if ($helpful) {
            $this->increment('helpful_votes');
        } else {
            $this->increment('not_helpful_votes');
        }
    }
}
