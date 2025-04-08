<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Polygon extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'coordinates', 'color'];

    protected $casts = ['coordinates' => 'array']; // Automatiskai decodina JSON
}
