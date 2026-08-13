<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelompokKeroyokan extends Model
{
    use HasFactory;

    protected $table = 'kelompok_keroyokan';

    protected $fillable = [
        'kategori_id',
        'nama_kelompok',
        'deskripsi',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'kelompok_keroyokan_id');
    }

    public function batch(): HasMany
    {
        return $this->hasMany(BatchKeroyokan::class, 'kelompok_keroyokan_id');
    }
}
