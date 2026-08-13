<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['username','password','nama_lengkap','email','no_hp','role','status'];
    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function umkm(): HasOne { return $this->hasOne(Umkm::class, 'user_id'); }
    public function pesanan(): HasMany { return $this->hasMany(Pesanan::class, 'pembeli_id'); }
    public function payments(): HasMany { return $this->hasMany(Payment::class, 'user_id'); }
    public function ulasan(): HasMany { return $this->hasMany(Ulasan::class, 'pembeli_id'); }
    public function logAktivitas(): HasMany { return $this->hasMany(LogAktivitas::class, 'user_id'); }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isSeller(): bool { return $this->role === 'penjual'; }
    public function isBuyer(): bool { return $this->role === 'pembeli'; }
    public function isActive(): bool { return $this->status === 'aktif'; }

    public function dashboardPath(): string
    {
        return match ($this->role) {
            'admin' => '/admin',
            'penjual' => '/penjual',
            default => '/pembeli',
        };
    }
}
