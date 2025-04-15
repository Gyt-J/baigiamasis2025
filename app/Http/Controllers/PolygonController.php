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
        return response() -> json(Polygon::with('statusas')->get());
    }

    /*public function update(Request $request, $id)
    {
        $polygon = Polygon::findOrFail($id);

        $polygon->name = $request->input('name');
        // $polygon->coordinates = json_encode($request->input('coordinates'));
        $coordinates = $request->input('coordinates');

        if (!is_array($coordinates))
        {
            return response()->json(['error' => 'Invalid coordinates format'], 400);
        }

        $polygon->coordinates = json_encode($coordinates);
        $polygon->color = $request->input('color');

        \Log::info('Incoming coordinates:', ['data' => $request->input('coordinates')]);


        $polygon->save();

        return response()->json($polygon);
    }*/

    public function update(Request $request, $id)
    {
        $polygon = Polygon::findOrFail($id);

        $polygon->name = $request->input('name');
        $polygon->color = $request->input('color');
    
        $coordinates = $request->input('coordinates');
    
        // Keicia i array jei string
        if (is_string($coordinates)) {
            $decoded = json_decode($coordinates, true);
    
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return response()->json(['error' => 'Neteisingas koordinaciu formatas'], 400);
            }
    
            $coordinates = $decoded;
        }
    
        // Tikrina kad butu teisingas koordinaciu formatas
        if (
            !is_array($coordinates) ||
            !is_array($coordinates[0]) ||
            !is_array($coordinates[0][0])
        ) {
            return response()->json(['error' => 'Koordinates turi buti tinkamo formato'], 400);
        }
    
        $polygon->coordinates = $coordinates;
        $polygon->save();
    
        return response()->json($polygon);
    }

}
