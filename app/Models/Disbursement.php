<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Disbursement extends Model
{
    protected $fillable = [
        'umkm_id',
        'rekening_bank_id',
        'rekening_bank_snapshot',
        'jumlah',
        'status',
        'catatan',
        'requested_by',
        'admin_id',
        'diajukan_at',
        'dibayar_at',
        'ditolak_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'rekening_bank_snapshot' => 'array',
            'diajukan_at' => 'datetime',
            'dibayar_at' => 'datetime',
            'ditolak_at' => 'datetime',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function rekeningBank(): BelongsTo
    {
        return $this->belongsTo(RekeningBank::class, 'rekening_bank_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function pesanan(): BelongsToMany
    {
        return $this->belongsToMany(Pesanan::class, 'disbursement_pesanan');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'diajukan');
    }

    public function scopeDiajukan(Builder $query): Builder
    {
        return $query->where('status', 'diajukan');
    }

    public function scopeDibayar(Builder $query): Builder
    {
        return $query->where('status', 'dibayar');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['dibayar', 'ditolak']);
    }

    public function getFormattedRekeningSnapshotAttribute(): string
    {
        if (is_array($this->rekening_bank_snapshot) && !empty($this->rekening_bank_snapshot)) {
            $bank = $this->rekening_bank_snapshot['nama_bank'] ?? '';
            $no = $this->rekening_bank_snapshot['nomor_rekening'] ?? '';
            $nama = $this->rekening_bank_snapshot['atas_nama'] ?? '';
            return trim("{$bank} - {$no} a.n. {$nama}", " - a.n.");
        }

        if ($this->rekeningBank) {
            return $this->rekeningBank->formatted_snapshot;
        }

        return '-';
    }
}
