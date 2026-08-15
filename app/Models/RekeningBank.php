<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekeningBank extends Model
{
    use HasFactory;

    protected $table = 'rekening_bank';

    protected $fillable = [
        'umkm_id',
        'nama_bank',
        'nomor_rekening',
        'atas_nama',
        'aktif',
        'urutan',
    ];

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class, 'umkm_id');
    }

    public function scopeForUmkm($query, int $umkmId)
    {
        return $query->where('umkm_id', $umkmId);
    }

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true)->orderBy('urutan', 'asc')->orderBy('nama_bank', 'asc');
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'rekening_bank_id');
    }

    public function getFormattedSnapshotAttribute(): string
    {
        return "{$this->nama_bank} - {$this->nomor_rekening} a.n. {$this->atas_nama}";
    }
}
