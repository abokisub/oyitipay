<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointWaveTransaction extends Model
{
    use HasFactory;

    protected $table = 'pointwave_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'fee',
        'status',
        'reference',
        'pointwave_transaction_id',
        'pointwave_customer_id',
        'account_number',
        'bank_code',
        'account_name',
        'narration',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include deposit transactions.
     */
    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    /**
     * Scope a query to only include transfer transactions.
     */
    public function scopeTransfers($query)
    {
        return $query->where('type', 'transfer');
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include successful transactions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'successful');
    }
}
