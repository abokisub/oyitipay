<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'user';
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'phone',
        'bal',
        'type',
        'apikey',
        'bal',
        'kyc',
        'refbal',
        'ref',
        'type',
        'date',
        'status',
        'dob',
        'bvn',
        'nin',
        'next_of_kin',
        'occupation',
        'marital_status',
        'religion',
        'xixapay_kyc_data',
        'address',
        'city',
        'state',
        'postal_code',
        'id_card_path',
        'utility_bill_path',
        'customer_id',
        'kyc_status',
        'kyc_submitted_at',
        'app_key',
        'habukhan_key',
        'kyc_documents',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'apikey',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'datetime',
        'next_of_kin' => 'array',
        'xixapay_kyc_data' => 'array',
        'kyc_documents' => 'array',
    ];
}
