<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function formation() {
        return $this->belongsTo(Formation::class);
    }

    public function ressource() {
        return $this->belongsTo(Ressoource::class);
    }

}
