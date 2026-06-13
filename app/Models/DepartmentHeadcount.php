<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentHeadcount extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'target_headcount',
    ];

    protected $casts = [
        'target_headcount' => 'integer',
    ];
}
