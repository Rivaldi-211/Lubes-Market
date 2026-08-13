<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $attributes = [
        'status' => 'CREATING',
    ];

    protected $fillable = [
        'user_id',
        'reference_id',
        'xendit_payment_request_id',
        'xendit_payment_id',
        'amount',
        'payment_method',
        'status',
        'provider_request_started_at',
        'qr_string',
        'raw_response',
        'expires_at',
        'paid_at',
        'stock_restored_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'raw_response' => 'array',
        'provider_request_started_at' => 'datetime',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'stock_restored_at' => 'datetime',
    ];

    public function isStockRestored(): bool
    {
        return $this->stock_restored_at !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pesanan(): BelongsToMany
    {
        return $this->belongsToMany(
            Pesanan::class,
            'payment_pesanan',
            'payment_id',
            'pesanan_id'
        )->withTimestamps();
    }
}
