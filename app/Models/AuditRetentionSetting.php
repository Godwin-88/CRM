<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditRetentionSetting extends Model
{
    protected $table = 'audit_retention_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'integer',
    ];
}
