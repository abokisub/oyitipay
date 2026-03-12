<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use HasFactory;

    protected $table = 'service_beneficiaries';

    protected $fillable = [
        'user_id',
        'service_type',
        'identifier',
        'network_or_provider',
        'name',
        'is_favorite',
        'last_used_at'
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
