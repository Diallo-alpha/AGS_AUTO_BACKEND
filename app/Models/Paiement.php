<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use HasFactory;
    public function formation() {
        return $this->belongsTo(Formation::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

}
