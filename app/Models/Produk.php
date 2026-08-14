<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Produk extends Model
{
    use HasFactory;
    protected $table='produk';
    protected $fillable=['umkm_id','kategori_id','kelompok_keroyokan_id','nama_produk','harga','stok_status','stok_jumlah','deskripsi','foto','is_promo','harga_promo','promo_mulai','promo_selesai','label_promo'];
    protected function casts(): array { return ['harga'=>'decimal:2','stok_jumlah'=>'integer','is_promo'=>'boolean','harga_promo'=>'decimal:2','promo_mulai'=>'datetime','promo_selesai'=>'datetime']; }
    public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class, 'umkm_id'); }
    public function kategori(): BelongsTo { return $this->belongsTo(Kategori::class, 'kategori_id'); }
    public function kelompokKeroyokan(): BelongsTo { return $this->belongsTo(KelompokKeroyokan::class, 'kelompok_keroyokan_id'); }
    public function pesanan(): HasMany { return $this->hasMany(Pesanan::class, 'produk_id'); }
    public function ulasan(): HasMany { return $this->hasMany(Ulasan::class, 'produk_id'); }
    public function isAvailable(): bool { return $this->stok_status !== 'Habis' && $this->stok_jumlah > 0; }
    public function effectiveStockStatus(): string { return $this->stok_jumlah <= 0 ? 'Habis' : $this->stok_status; }

    public function isPromoAktif(): bool {
        if (!$this->is_promo || $this->harga_promo === null) return false;
        if ($this->promo_mulai && $this->promo_mulai > now()) return false;
        if ($this->promo_selesai && $this->promo_selesai < now()) return false;
        return true;
    }

    public function hargaEfektif(): float {
        return $this->isPromoAktif() ? (float) $this->harga_promo : (float) $this->harga;
    }

    public function diskonPersen(): int {
        if (!$this->isPromoAktif() || (float)$this->harga == 0) return 0;
        return (int) round((((float)$this->harga - (float)$this->harga_promo) / (float)$this->harga) * 100);
    }
}
