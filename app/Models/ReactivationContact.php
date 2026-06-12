<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReactivationContact extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'config_id',
        'contact_id',
        'drip_enrolment_id',
        'status',
        'last_interaction_at',
        're_engaged_at',
    ];

    protected $casts = [
        'last_interaction_at' => 'datetime',
        're_engaged_at' => 'datetime',
    ];

    public function config(): BelongsTo
    {
        return $this->belongsTo(ReactivationConfig::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function dripEnrolment(): BelongsTo
    {
        return $this->belongsTo(DripEnrolment::class);
    }
}
