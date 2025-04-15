<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Polygon extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'coordinates', 'color', 'rc_kodas', 'statusas_id'];

    protected $casts = ['coordinates' => 'array']; // Automatiskai decodina JSON

    public function statusas()
    {
        return $this->belongsTo(Statusas::class, 'statusas_id');
    }
}
