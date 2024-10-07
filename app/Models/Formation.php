<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{

    use HasFactory;
    protected $guarded = [];

    public function notes() {
        return $this->hasMany(NoteFormation::class);
    }

    public function paiements() {
        return $this->hasMany(Paiement::class);
    }

    public function photos() {
        return $this->hasMany(PhotoFormation::class);
    }

    public function videos() {
        return $this->hasMany(Video::class);
    }

    public function progressions()
    {
        return $this->hasMany(Progression::class);
    //
    }
    public function utilisateurs()
    {
        return $this->belongsToMany(User::class, 'user_formations')
                    ->withTimestamps()
                    ->withPivot('date_achat');
    }
}
