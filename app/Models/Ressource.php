<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ressource extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function videos() {
        return $this->hasMany(Video::class);
    }
    //ressources
    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
