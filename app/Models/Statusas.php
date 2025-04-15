<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statusas extends Model
{
    use HasFactory;

    protected $table = 'statusai';
    protected $fillable = ['statusas'];

    public $timestamps = false;

    public function polygons()
    {
        return $this->hasMany(Polygon::class, 'statusas_id');
    }
}