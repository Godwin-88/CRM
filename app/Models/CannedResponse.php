<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CannedResponse extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'category_tag',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'canned_response_favorites', 'canned_response_id', 'user_id')
            ->withTimestamps();
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function resolveVariables(array $context = []): string
    {
        $body = $this->body;

        $variables = [
            '{first_name}' => $context['first_name'] ?? '',
            '{last_name}' => $context['last_name'] ?? '',
            '{ticket_number}' => $context['ticket_number'] ?? '',
            '{agent_name}' => $context['agent_name'] ?? '',
            '{account_name}' => $context['account_name'] ?? '',
        ];

        return str_replace(array_keys($variables), array_values($variables), $body);
    }
}
