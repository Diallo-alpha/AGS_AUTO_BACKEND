<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    use HasFactory;
    public function notes() {
        return $this->hasMany(NoteFoorrmation::class);
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
}
