<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointWaveVirtualAccount extends Model
{
    use HasFactory;

    protected $table = 'pointwave_virtual_accounts';

    protected $fillable = [
        'user_id',
        'customer_id',
        'account_number',
        'account_name',
        'bank_name',
        'bank_code',
        'status',
        'external_reference',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the virtual account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
