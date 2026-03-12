<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointWaveKYC extends Model
{
    use HasFactory;

    protected $table = 'pointwave_kyc';

    protected $fillable = [
        'user_id',
        'id_type',
        'id_number_encrypted',
        'kyc_status',
        'tier',
        'daily_limit',
        'verified_at',
    ];

    protected $casts = [
        'daily_limit' => 'decimal:2',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the KYC record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the decrypted ID number.
     */
    public function getIdNumberAttribute()
    {
        return decrypt($this->id_number_encrypted);
    }

    /**
     * Set the encrypted ID number.
     */
    public function setIdNumberAttribute($value)
    {
        $this->attributes['id_number_encrypted'] = encrypt($value);
    }
}
