<?php

namespace App\Http\Controllers;

use App\Models\Polygon;
use App\Models\Paseliai;
use Illuminate\Http\Request;

class PaselioController extends Controller
{
    public function update(Polygon $polygon, Request $request)
    {
        $validated = $request->validate([
            'paselio_id' => 'required|exists:paseliai,id',
            'metai' => 'required|integer'
        ]);

        try
        {
            $istorija = $polygon->paseliu_istorija ?: [];

            $crop = Paseliai::findOrFail($validated['paselio_id']);

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