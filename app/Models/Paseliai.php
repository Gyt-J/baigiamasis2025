<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paseliai extends Model
{
    use HasFactory;

    protected $table = 'paseliai';

    protected $fillable = [ 'pavadinimas', 'kategorija', 'rotacijos_grupe' ];

    protected $casts = ['paseliu_istorija' => 'array'];

    public function polygons()
    {
        return $this->hasMany(Polygon::class, 'paselio_id');
    }
}