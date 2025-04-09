<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Polygon;
use Psr\Http\Message\RequestInterface;

use function GuzzleHttp\json_decode;

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

    public function update(Request $request, $id)
    {
        $polygon = Polygon::findOrFail($id);

        $polygon->name = $request->input('name');
        $polygon->coordinates = json_encode($request->input('coordinates'));
        $polygon->color = $request->input('color');

        $polygon->save();

        return response()->json($polygon);
    }
}
