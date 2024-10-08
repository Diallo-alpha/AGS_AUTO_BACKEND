<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Paiement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'cart' => 'array',
        ];
    }
    // protected $casts = [

    // ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function notes() {
        return $this->hasMany(NoteFormation::class);
    }
    //progression
    public function progressions()
    {
        return $this->hasMany(Progression::class);
    }

    public function paiements() {
        return $this->hasMany(Paiement::class);
    }

    public function formations()
    {
        return $this->belongsToMany(Formation::class, 'user_formations')
                    ->withTimestamps()
                    ->withPivot('date_achat');
    }

    public function acheterFormation(Formation $formation)
    {
        $this->formations()->attach($formation->id, ['date_achat' => now()]);
    }
}
