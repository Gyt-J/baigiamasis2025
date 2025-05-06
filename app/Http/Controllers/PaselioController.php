<?php

namespace App\Http\Controllers;

use App\Models\Polygon;
use App\Models\Paselis;
use Illuminate\Http\Request;

class PaselioController extends Controller
{
    public function index()
    {
        return Paselis::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pavadinimas' => 'required|string|unique:paseliai,pavadinimas',
            'rotacijos_grupe' => 'required|integer|between:1,4'
        ]);

        $paselis = Paselis::create($validated);
        return response()->json($paselis, 201);
    }

    public function update(Polygon $polygon, Request $request)
    {
        $validated = $request->validate([
            'paselio_id' => 'required|exists:paseliai,id',
            'metai' => 'required|integer'
        ]);

        try
        {
            $istorija = $polygon->paseliu_istorija ?: [];

            $crop = Paselis::findOrFail($validated['paselio_id']);

            array_unshift($istorija, [
                'metai' => $validated['metai'],
                'paselio_id' => $validated['paselio_id'],
                'pavadinimas' => $crop->pavadinimas,
                'rotacijos_grupe' => $crop->rotacijos_grupe
            ]);

            $istorija = array_slice($istorija, 0, 5);

            $polygon->update([
                'paselio_id' => $validated['paselio_id'],
                'paseliu_istorija' => $istorija
            ]);

            return response()->json([
                'success' => true,
                'polygon' => $polygon->fresh()
            ]);
        }

        catch (\Exception $e)
        {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }

        /*$istorija = $polygon->paseliu_istorija ?? [];

        array_unshift($istorija, [
            'metai' => $validated['metai'],
            'paselio_id' => $validated['paselio_id']
        ]);

        $polygon->update([
            'paselio_id' => $validated['paselio_id'],
            'paseliu_istorija' => array_slice($istorija, 0, 5)
        ]);

        return response()->json($polygon->load('paseliai'));*/
    }
}