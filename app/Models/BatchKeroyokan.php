<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchKeroyokan extends Model
{
    use HasFactory;

    protected $table = 'batch_keroyokan';

    protected $fillable = [
        'pembeli_id',
        'kelompok_keroyokan_id',
        'target_jumlah',
        'total_harga',
    ];

    protected function casts(): array
    {
        return [
            'target_jumlah' => 'integer',
            'total_harga' => 'decimal:2',
        ];
    }

    public function pembeli(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembeli_id');
    }

    public function kelompokKeroyokan(): BelongsTo
    {
        return $this->belongsTo(KelompokKeroyokan::class, 'kelompok_keroyokan_id');
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'batch_keroyokan_id');
    }

    public function calculateOverallStatus(): string
    {
        $statuses = $this->pesanan()->pluck('status');
        if ($statuses->isEmpty()) {
            return 'Menunggu';
        }

        if ($statuses->every(fn($s) => $s === 'Dibatalkan')) {
            return 'Dibatalkan';
        }

        if ($statuses->every(fn($s) => $s === 'Selesai')) {
            return 'Selesai';
        }

        if ($statuses->contains('Diproses') || $statuses->contains('Selesai')) {
            return 'Diproses';
        }

        return 'Menunggu';
    }
}
