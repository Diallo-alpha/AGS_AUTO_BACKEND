<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Progression extends Model
{
    use HasFactory;
    protected $guarded = [];
    
      // Relation avec la formation
      public function formation()
      {
          return $this->belongsTo(Formation::class);
      }

      // Relation avec l'utilisateur
      public function user()
      {
          return $this->belongsTo(User::class);
      }
}
