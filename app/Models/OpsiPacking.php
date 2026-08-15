<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpsiPacking extends Model
{
    protected $table = 'opsi_packing';
    protected $fillable = ['nama', 'deskripsi', 'biaya', 'aktif', 'urutan'];

    protected function casts(): array
    {
        return [
            'biaya' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
