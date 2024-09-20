<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreserviceRequest;
use App\Http\Requests\UpdateserviceRequest;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $service = Service::all();
        return response()->json($service);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreserviceRequest $request)
    {
     // Vérifier que l'utilisateur est bien connecté et qu'il a le rôle d'admin
     if (!Auth::check() || !Auth::user()->hasRole('admin')) {
        return response()->json(['message' => 'Accès refusé'], 403);
    }
    // Valider les données de la requête
    $validated = $request->validated();
    if($request->hasfile('photo')){
        //stocker l'image dans le storage
        $path = $request->file('photo')->store('services_photos', 'public');
        $validated['photo'] = $path;
    }
    // Créer un nouveau service
    $service = Service::create($validated);
    //reponse
    return response()->json([
       'message' => 'Service créé avec succès',
       'service' => $service
    ], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        return response()->json($service);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateserviceRequest $request, Service $service)
    {
        //
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }
        $validated = $request->validated();

        if($request->hasfile('photo')){
            $file = $request->file('photo');
            \Log::info('Fichier reçu:', ['name' => $file->getClientOriginalName(),'size' => $file->getSize()]);
            // Supprimer l'ancienne photo s'il en existe un
            if ($service->photo) {
                Storage::disk('public')->delete($service->photo);
            }
            // Stocker le nouveau logo et mettre à jour l'attribut 'logo'
            $path = $file->store('services_photos', 'public');
            $validated['photo'] = $path;
        }
        // Mettre à jour les informations du service
        $service->update($validated);

        return response()->json([
           'message' => 'Service mis à jour avec succès',
           'service' => $service
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        // Vérifier que l'utilisateur est bien connecté et qu'il a le rôle d'admin
        if (!Auth::check() ||!Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }
        // Supprimer le service et ses photos s'il en a
        if ($service->photo) {
            Storage::disk('public')->delete($service->photo);
        }
        $service->delete();
        // Retourner une réponse de succès
        return response()->json(['message' => 'Service supprimé avec succès']);
    }
}
