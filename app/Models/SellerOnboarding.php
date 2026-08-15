<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerOnboarding extends Model
{
    protected $table = 'seller_onboarding';
    protected $fillable = ['umkm_id', 'jawaban'];

    protected function casts(): array
    {
        return [
            'jawaban' => 'array',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }
}
