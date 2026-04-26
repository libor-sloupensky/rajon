<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\OvereniEmailu;
use App\Notifications\ResetHesla;

class Uzivatel extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $table = 'uzivatele';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'jmeno',
        'prijmeni',
        'email',
        'telefon',
        'heslo',
        'google_id',
        'role',
        'region',
        'mesto',
        'psc',
        'gps_lat',
        'gps_lng',
        'email_overen_v',
        'posledni_prihlaseni',
    ];

    protected $hidden = [
        'heslo',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_overen_v' => 'datetime',
            'posledni_prihlaseni' => 'datetime',
            'heslo' => 'hashed',
            'gps_lat' => 'float',
            'gps_lng' => 'float',
        ];
    }

    /** Má uživatel vyplněnou adresu s GPS? */
    public function maAdresu(): bool
    {
        return !empty($this->gps_lat) && !empty($this->gps_lng);
    }

    public function getAuthPassword(): string
    {
        return $this->heslo;
    }

    public function getAuthPasswordName(): string
    {
        return 'heslo';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_overen_v);
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill(['email_overen_v' => $this->freshTimestamp()])->save();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new OvereniEmailu);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetHesla($token));
    }

    public function jeAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function jeFransizan(): bool
    {
        return $this->role === 'fransizan';
    }

    public function akce(): HasMany
    {
        return $this->hasMany(Akce::class, 'uzivatel_id');
    }

    public function rezervace(): HasMany
    {
        return $this->hasMany(Rezervace::class, 'uzivatel_id');
    }

    public function celejmeno(): string
    {
        return $this->jmeno . ' ' . $this->prijmeni;
    }
}
