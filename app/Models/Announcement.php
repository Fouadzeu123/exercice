<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['title', 'content', 'image_url', 'link', 'active'])]
class Announcement extends Model
{
    // Attributes cast
    protected $casts = [
        'active' => 'boolean',
    ];
}
