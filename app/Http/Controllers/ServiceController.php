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
        $services = Service::all();

        foreach ($services as $service) {
            $service->photo = $service->photo ? asset('storage/' . $service->photo) : null;
        }

        return response()->json($services);
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

        if ($request->hasfile('photo')) {
            // Stocker l'image dans le storage
            $path = $request->file('photo')->store('services_photos', 'public');
            $validated['photo'] = $path;
        }

        // Créer un nouveau service
        $service = Service::create($validated);

        // Ajouter l'URL complète de la photo
        $service->photo = $service->photo ? asset('storage/' . $service->photo) : null;

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
        // Ajouter l'URL complète de la photo
        $service->photo = $service->photo ? asset('storage/' . $service->photo) : null;

        return response()->json($service);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateserviceRequest $request, Service $service)
    {
        // Vérifier que l'utilisateur est bien connecté et qu'il a le rôle d'admin
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        if ($request->hasfile('photo')) {
            $file = $request->file('photo');
            \Log::info('Fichier reçu:', ['name' => $file->getClientOriginalName(), 'size' => $file->getSize()]);

            // Supprimer l'ancienne photo s'il y en a une
            if ($service->photo) {
                Storage::disk('public')->delete($service->photo);
            }

            // Stocker la nouvelle photo et mettre à jour l'attribut 'photo'
            $path = $file->store('services_photos', 'public');
            $validated['photo'] = $path;
        }

        // Mettre à jour les informations du service
        $service->update($validated);

        // Ajouter l'URL complète de la photo
        $service->photo = $service->photo ? asset('storage/' . $service->photo) : null;

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
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Supprimer la photo s'il y en a une
        if ($service->photo) {
            Storage::disk('public')->delete($service->photo);
        }

        // Supprimer le service
        $service->delete();

        return response()->json(['message' => 'Service supprimé avec succès']);
    }

}
