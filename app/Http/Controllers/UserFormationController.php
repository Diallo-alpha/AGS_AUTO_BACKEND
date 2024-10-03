<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserFormationRequest;
use App\Http\Requests\UpdateUserFormationRequest;
use App\Models\UserFormation;
use App\Models\Formation;
use Illuminate\Http\Request;

class UserFormationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserFormationRequest $request)
    {
        // Stocker les informations dans la table utilisateur_formation
        $userFormation = UserFormation::create([
            'user_id' => $request->user_id,
            'formation_id' => $request->formation_id,
            'date_achat' => now(),
        ]);

        return response()->json(['message' => 'Formation achetée avec succès!', 'data' => $userFormation], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserFormation $userFormation)
    {
        return response()->json($userFormation);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserFormation $userFormation)
    {
        // Si vous avez besoin d'une logique d'édition, vous pouvez la gérer ici.
        return response()->json($userFormation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserFormationRequest $request, UserFormation $userFormation)
    {
        // Mettre à jour les informations de la formation achetée
        $userFormation->update($request->validated());

        return response()->json(['message' => 'Formation mise à jour avec succès!', 'data' => $userFormation]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserFormation $userFormation)
    {
        $userFormation->delete();

        return response()->json(['message' => 'Formation supprimée avec succès!']);
    }

    /**
     * Affiche toutes les formations d'un utilisateur.
     */
    public function index(Request $request)
    {
        $user = $request->user(); // Récupère l'utilisateur connecté
        $formations = $user->formations; // Récupère les formations associées à l'utilisateur

        return response()->json($formations);
    }
}
