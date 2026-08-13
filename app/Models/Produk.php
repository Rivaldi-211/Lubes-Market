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
    protected $fillable=['umkm_id','kategori_id','kelompok_keroyokan_id','nama_produk','harga','stok_status','stok_jumlah','deskripsi','foto'];
    protected function casts(): array { return ['harga'=>'decimal:2','stok_jumlah'=>'integer']; }
    public function umkm(): BelongsTo { return $this->belongsTo(Umkm::class, 'umkm_id'); }
    public function kategori(): BelongsTo { return $this->belongsTo(Kategori::class, 'kategori_id'); }
    public function kelompokKeroyokan(): BelongsTo { return $this->belongsTo(KelompokKeroyokan::class, 'kelompok_keroyokan_id'); }
    public function pesanan(): HasMany { return $this->hasMany(Pesanan::class, 'produk_id'); }
    public function ulasan(): HasMany { return $this->hasMany(Ulasan::class, 'produk_id'); }
    public function isAvailable(): bool { return $this->stok_status !== 'Habis' && $this->stok_jumlah > 0; }
    public function effectiveStockStatus(): string { return $this->stok_jumlah <= 0 ? 'Habis' : $this->stok_status; }
}
