<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZonaPengiriman extends Model
{
    protected $table = 'zona_pengiriman';
    protected $fillable = ['nama_zona', 'keterangan', 'biaya', 'aktif', 'urutan'];

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
