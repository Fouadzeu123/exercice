<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'amount',
        'max_usages',
        'usages',
    ];

    protected $casts = [
        'amount' => 'float',
        'max_usages' => 'integer',
        'usages' => 'integer',
    ];
}
