<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAVIPProduct extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'active' => 'boolean',
        'purchased_at' => 'datetime',
    ];

    /**
     * Relation: L'achat appartient à un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: L'achat est lié à un produit AVIP
     */
    public function avipProduct(): BelongsTo
    {
        return $this->belongsTo(AVIPProduct::class)->withTrashed();
    }
}
