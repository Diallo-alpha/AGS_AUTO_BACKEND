<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormationsRequest;
use App\Http\Requests\UpdateFormationsRequest;
use App\Models\Formation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FormationsController extends Controller
{
    public function index()
    {
        $formations = Formation::all();
        foreach ($formations as $formation) {
            $formation->image = $formation->image ? asset('storage/' . $formation->image) : null;
        }
        return response()->json($formations);
    }

    public function store(StoreFormationsRequest $request)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validatedData = $request->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('formations_image', 'public');
            $validatedData['image'] = $path;
        }

        $formation = Formation::create($validatedData);

        $formation->image = $formation->image ? asset('storage/' . $formation->image) : null;

        return response()->json(['message' => 'Formation créée avec succès', 'formation' => $formation], 201);
    }

    public function show(Formation $formation)
    {
        $formation->image = $formation->image ? asset('storage/' . $formation->image) : null;
        return response()->json($formation);
    }

    public function update(UpdateFormationsRequest $request, Formation $formation)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validatedData = $request->validated();

        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($formation->image) {
                Storage::disk('public')->delete($formation->image);
            }

            $path = $request->file('image')->store('formations_image', 'public');
            $validatedData['image'] = $path;
        }

        $formation->update($validatedData);

        $formation->image = $formation->image ? asset('storage/' . $formation->image) : null;

        return response()->json(['message' => 'Formation mise à jour avec succès', 'formation' => $formation]);
    }

    public function destroy(Formation $formation)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Supprimer l'image associée si elle existe
        if ($formation->image) {
            Storage::disk('public')->delete($formation->image);
        }

        $formation->delete();

        return response()->json(['message' => 'Formation supprimée avec succès']);
    }
}
