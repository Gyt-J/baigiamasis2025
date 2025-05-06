<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Polygon extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'coordinates', 'color', 'plotas', 'rc_kodas', 'statusas_id', 'paselio_id', 'paseliu_istorija'];

    protected $casts = [
        'coordinates' => 'array',
        'paseliu_istorija' => 'array'
    ]; // Automatiskai decodina JSON

    public function statusas()
    {
        return $this->belongsTo(Statusas::class, 'statusas_id');
    }

    public function currentCrop()
    {
        return $this->belongsTo(Paselis::class, 'paselio_id');
    }

    /*public function paseliu_istorija()
    {
        return $this->hasMany(PaselisIstorija::class);
    }*/
}
