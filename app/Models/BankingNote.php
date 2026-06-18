<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankingNote extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'banking_relationship_id',
        'user_id',
        'content',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(BankingRelationship::class, 'banking_relationship_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}