<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AVIPProduct extends Model
{
    use SoftDeletes;

    protected $table = 'avip_products';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'daily_salary' => 'float',
        'referral_reward' => 'float',
        'required_vip_level' => 'integer',
        'avip_level' => 'integer',
        'active' => 'boolean',
        'is_limited' => 'boolean',
        'stock_quantity' => 'integer',
        'limited_purchase_count' => 'integer',
    ];

    /**
     * Relation: Un produit AVIP peut avoir plusieurs achats utilisateurs
     */
    public function userAVIPProducts()
    {
        return $this->hasMany(UserAVIPProduct::class);
    }
}
