<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Umkm extends Model
{
    use HasFactory;
    protected $table='umkm';
    protected $fillable=['user_id','nama_umkm','pemilik','alamat','no_hp','deskripsi','kategori_usaha','tahun_berdiri','jumlah_karyawan','instagram','foto','status'];
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function produk(): HasMany { return $this->hasMany(Produk::class, 'umkm_id'); }
    public function rekomendasiStrategi(): HasMany { return $this->hasMany(RekomendasiStrategi::class, 'umkm_id'); }
    public function rekomendasiBelumDibaca(): int { return $this->rekomendasiStrategi()->where('dibaca', false)->count(); }
}
