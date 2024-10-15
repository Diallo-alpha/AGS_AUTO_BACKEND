<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Progression extends Model
{
    use HasFactory;

    protected $fillable = ['formation_id', 'user_id', 'pourcentage', 'completed', 'videos_regardees'];

    protected $casts = [
        'completed' => 'boolean',
        'videos_regardees' => 'array',
    ];

    public function marquerVideoCommeVue($videoId)
    {
        $videosRegardees = $this->videos_regardees ?? [];
        if (!in_array($videoId, $videosRegardees)) {
            $videosRegardees[] = $videoId;
            $this->videos_regardees = $videosRegardees;
            $this->pourcentage = $this->calculerPourcentage();
            $this->completed = $this->verifierComplete();
            $this->save();
        }
    }

    private function calculerPourcentage()
    {
     
        $totalVideos = Formation::find($this->formation_id)->videos()->count();
        return ($totalVideos > 0) ? (count($this->videos_regardees) / $totalVideos) * 100 : 0;
    }

    private function verifierComplete()
    {
        $totalVideos = Formation::find($this->formation_id)->videos()->count();
        return count($this->videos_regardees) == $totalVideos;
    }
}
