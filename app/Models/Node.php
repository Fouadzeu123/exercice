<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Node extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    /**
     * Transformer les montants en nombres flottants automatiquement
     */
    protected $casts = [
        'amount' => 'float',
        'generation_profit' => 'float',
        'referral_reward' => 'float',
        'active' => 'boolean',
        'is_limited' => 'boolean',
        'duration' => 'integer', // en jours
        'technology_level' => 'integer',
    ];

    /**
     * Relation: Un nœud peut posséder plusieurs investissements utilisateurs (UserNode)
     */
    public function userNodes()
    {
        return $this->hasMany(UserNode::class);
    }
}