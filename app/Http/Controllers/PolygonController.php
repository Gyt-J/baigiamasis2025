<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Polygon;

class PolygonController extends Controller
{
    public function store(Request $request)
    {
        $polygon = Polygon::create([
            'name' => $request->name,
            'coordinates' => $request->coordinates, // Tikesis JSON formato
            'color' => $request->color
        ]);

        return response() -> json($polygon, 201);
    }

    public function index()
    {
        return response() -> json(Polygon::all());
    }
}
