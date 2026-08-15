<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Disbursement extends Model
{
    protected $fillable = ['umkm_id', 'jumlah', 'status', 'catatan', 'dibayar_at', 'admin_id'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'dibayar_at' => 'datetime',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function pesanan(): BelongsToMany
    {
        return $this->belongsToMany(Pesanan::class, 'disbursement_pesanan');
    }
}
