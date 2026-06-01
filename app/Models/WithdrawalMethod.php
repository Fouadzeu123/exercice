<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalMethod extends Model
{
    protected $fillable = [
        'user_id',
        'operator',
        'full_name',
        'phone',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns the withdrawal method.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
