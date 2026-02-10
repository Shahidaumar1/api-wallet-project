<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiClient extends Model
{
    protected $fillable = [
        'name',
        'api_key',
        'api_secret',
        'website_url',
        'webhook_url',
        'allowed_ips',
        'is_active',
        'payment_methods',
        'contact_email',
        'contact_phone',
    ];

    protected $hidden = ['api_secret'];

    protected $casts = [
        'payment_methods' => 'array',
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
