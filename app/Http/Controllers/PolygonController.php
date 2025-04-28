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
        $validated = $request->validate([
            'name' => 'required|string',
            'coordinates' => 'required|array',
            'color' => 'nullable|string',
            'plotas' => 'nullable|numeric',
            'statusas_id' => 'nullable|integer|exists:statusai,id'
        ]);
    
        // Convert coordinates to consistent format
        $validated['coordinates'] = array_values($validated['coordinates']);
    
        $polygon = Polygon::create($validated);
        return response()->json($polygon->load('statusas'), 201);
    }
    
    public function index()
    {
        //return response() -> json(Polygon::with('statusas', 'currentCrop', 'paseliu_istorija')->get());
        return Polygon::with(['statusas', 'currentCrop'])->get();
    }

    /*public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'coordinates' => 'required|string',
            'color' => 'required|string',
            'plotas' => 'nullable|numeric',
            'statusas_id' => 'nullable|integer|exists:statusai,id'
        ]);

        $polygon = Polygon::create($validated);
        
        return response()->json($polygon->load('statusas'), 201);
    }

    public function index()
    {
        return response() -> json(Polygon::with('statusas')->get());
    }*/

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
