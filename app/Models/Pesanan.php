<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Pesanan extends Model
{
    use HasFactory;
    protected $table='pesanan';
    protected $fillable=['pembeli_id','produk_id','jumlah','total_harga','metode_pembayaran','bukti_pembayaran','alamat_pengiriman','no_hp_pembeli','status','catatan','tanggal_pesan'];
    protected function casts(): array { return ['jumlah'=>'integer','total_harga'=>'decimal:2','tanggal_pesan'=>'datetime']; }
    public function pembeli(): BelongsTo { return $this->belongsTo(User::class, 'pembeli_id'); }
    public function produk(): BelongsTo { return $this->belongsTo(Produk::class, 'produk_id'); }
    public function ulasan(): HasOne { return $this->hasOne(Ulasan::class, 'pesanan_id'); }
}
