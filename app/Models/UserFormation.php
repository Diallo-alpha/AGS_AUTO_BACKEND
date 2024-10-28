<?php
// app/Models/UserFormation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserFormation extends Model
{
    use HasFactory;

    protected $table = 'user_formations';
    protected $guarded = [];
    protected $dates = ['date_achat'];

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

    // Relation avec la progression via formation_id et user_id
    public function progression()
    {
        return $this->hasOne(Progression::class, 'formation_id', 'formation_id')
                    ->where('user_id', $this->user_id);
    }
}
