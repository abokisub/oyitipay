<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashbackSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type',
        'cashback_amount',
    ];
}
