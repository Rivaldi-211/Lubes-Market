<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class Pesanan extends Model
{
    use HasFactory;
    protected $table='pesanan';
    protected $fillable=['pembeli_id','batch_keroyokan_id','produk_id','jumlah','total_harga','ongkos_kirim','biaya_packing','komisi_admin','pendapatan_penjual','opsi_packing','zona_pengiriman','metode_pembayaran','rekening_bank_id','rekening_bank_snapshot','bukti_pembayaran','status_pembayaran','alamat_pengiriman','no_hp_pembeli','status','catatan','tanggal_pesan'];
    protected function casts(): array { return ['jumlah'=>'integer','total_harga'=>'decimal:2','ongkos_kirim'=>'decimal:2','biaya_packing'=>'decimal:2','komisi_admin'=>'decimal:2','pendapatan_penjual'=>'decimal:2','tanggal_pesan'=>'datetime']; }
    public function pembeli(): BelongsTo { return $this->belongsTo(User::class, 'pembeli_id'); }
    public function batchKeroyokan(): BelongsTo { return $this->belongsTo(BatchKeroyokan::class, 'batch_keroyokan_id'); }
    public function produk(): BelongsTo { return $this->belongsTo(Produk::class, 'produk_id'); }
    public function rekeningBank(): BelongsTo { return $this->belongsTo(RekeningBank::class, 'rekening_bank_id'); }
    public function ulasan(): HasOne { return $this->hasOne(Ulasan::class, 'pesanan_id'); }
    public function payments(): BelongsToMany { return $this->belongsToMany(Payment::class, 'payment_pesanan', 'pesanan_id', 'payment_id')->withTimestamps(); }
    public function disbursements(): BelongsToMany { return $this->belongsToMany(Disbursement::class, 'disbursement_pesanan'); }

    public function isPaid(): bool
    {
        if ($this->status_pembayaran === 'Sudah Dibayar') {
            return true;
        }
        if ($this->relationLoaded('payments')) {
            if ($this->payments->contains(fn($p) => $p->status === 'PAID')) {
                return true;
            }
        } else {
            if ($this->payments()->where('status', 'PAID')->exists()) {
                return true;
            }
        }

        if (in_array($this->status, ['Diproses', 'Selesai']) && $this->metode_pembayaran !== 'COD') {
            return true;
        }

        if ($this->metode_pembayaran === 'COD' && $this->status === 'Selesai') {
            return true;
        }

        return false;
    }

    public function getPaymentStatusInfoAttribute(): array
    {
        if ($this->status === 'Dibatalkan') {
            return [
                'label' => 'Dibatalkan',
                'class' => 'payment-cancelled',
                'icon'  => 'bi-x-circle-fill'
            ];
        }

        if ($this->isPaid()) {
            return [
                'label' => 'Sudah Dibayar',
                'class' => 'payment-paid',
                'icon'  => 'bi-check-circle-fill'
            ];
        }

        if ($this->metode_pembayaran === 'COD') {
            return [
                'label' => 'COD (Saat Terima)',
                'class' => 'payment-cod',
                'icon'  => 'bi-cash-stack'
            ];
        }

        return [
            'label' => 'Belum Dibayar',
            'class' => 'payment-unpaid',
            'icon'  => 'bi-exclamation-circle-fill'
        ];
    }
}
