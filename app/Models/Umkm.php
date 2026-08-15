<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Umkm extends Model
{
    use HasFactory;

    protected $table = 'umkm';

    protected $fillable = [
        'user_id',
        'nama_umkm',
        'pemilik',
        'alamat',
        'no_hp',
        'deskripsi',
        'kategori_usaha',
        'tahun_berdiri',
        'jumlah_karyawan',
        'instagram',
        'foto',
        'status',
        'status_verifikasi',
        'catatan_verifikasi',
        'verified_at',
        'verified_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'umkm_id');
    }

    public function sellerOnboarding(): HasOne
    {
        return $this->hasOne(SellerOnboarding::class, 'umkm_id');
    }

    public function rekomendasiStrategi(): HasMany
    {
        return $this->hasMany(RekomendasiStrategi::class, 'umkm_id');
    }

    public function rekeningBank(): HasMany
    {
        return $this->hasMany(RekeningBank::class, 'umkm_id');
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class, 'umkm_id');
    }

    public function rekomendasiBelumDibaca(): int
    {
        return $this->rekomendasiStrategi()->where('dibaca', false)->count();
    }

    public function hasActiveDisbursement(): bool
    {
        return $this->disbursements()->whereIn('status', ['diajukan', 'diproses'])->exists();
    }
}
