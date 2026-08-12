<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Ulasan extends Model
{
    use HasFactory;
    protected $table='ulasan';
    protected $fillable=['pesanan_id','produk_id','pembeli_id','rating','komentar'];
    protected function casts(): array { return ['rating'=>'integer']; }
    public function pesanan(): BelongsTo { return $this->belongsTo(Pesanan::class, 'pesanan_id'); }
    public function produk(): BelongsTo { return $this->belongsTo(Produk::class, 'produk_id'); }
    public function pembeli(): BelongsTo { return $this->belongsTo(User::class, 'pembeli_id'); }
}
