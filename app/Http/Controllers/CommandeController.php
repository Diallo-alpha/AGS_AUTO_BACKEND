<?php

namespace App\Http\Controllers;

use Log;
use App\Models\Commande;
use App\Models\Commande_produit;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreCommandeRequest;
use App\Http\Requests\UpdateCommandeRequest;

class CommandeController extends Controller
{
    /**
     * Affiche une liste de toutes les commandes.
     */
    public function index()
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $commandes = Commande::with('produits', 'user')->get();

        return response()->json($commandes, 200);
    }

    /**
     * Crée une nouvelle commande et ajoute des produits.
     */
    public function store(StoreCommandeRequest $request)
    {
        // \Log::info('Données reçues:', $request->all());

        // Les données sont déjà validées via StoreCommandeRequest
        $validatedData = $request->validated();

        try {
            // Crée la commande
            $commande = Commande::create([
                'user_id' => Auth::id(),
                'somme' => $validatedData['somme'],
                'status' => $validatedData['status'],
                'date' => $validatedData['date'],
            ]);

            // Insère les produits dans la table pivot
            foreach ($validatedData['produits'] as $produit) {
                Commande_produit::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $produit['produit_id'],
                    'quantite' => $produit['quantite'],
                    'prix_unitaire' => $produit['prix_unitaire'],
                ]);

                // Débogage pour chaque produit ajouté
                \Log::info('Produit ajouté : ', $produit);
            }

            // Si tout fonctionne, renvoyer une réponse de succès
            return response()->json(['message' => 'Commande créée avec succès'], 201);
        } catch (\Exception $e) {
            // En cas d'erreur, renvoyer une réponse avec un code 500
            \Log::error('Erreur lors de la création de la commande : ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de la création de la commande', 'erreur' => $e->getMessage()], 500);
        }
    }

    /**
     * Affiche une commande spécifique.
     */
    public function show($id)
    {
        $commande = Commande::with('produits')->find($id);

        if (!$commande) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        return response()->json($commande, 200);
    }

    /**
     * Met à jour une commande existante.
     */
    public function update(UpdateCommandeRequest $request, $id)
    {
        // Vérifier si l'utilisateur est connecté et est admin
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé - Réservé aux administrateurs'], 403);
        }

        $commande = Commande::with('user')->find($id);
        if (!$commande) {
            \Log::info('Commande non trouvée: ' . $id);
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        // Valider les données de la requête
        $validatedData = $request->validated();
        \Log::info('Données validées reçues:', $validatedData);

        $oldStatus = $commande->status;
        \Log::info('Ancien status: ' . $oldStatus);

        // Mettre à jour les informations de la commande
        $commande->update([
            'somme' => $validatedData['somme'] ?? $commande->somme,
            'status' => $validatedData['status'] ?? $commande->status,
            'date' => $validatedData['date'] ?? $commande->date,
        ]);

        \Log::info('Nouveau status: ' . $commande->status);

        // Si le status est passé à "liverer" et n'était pas déjà "liverer"
        if (($validatedData['status'] ?? $oldStatus) === 'liverer' && $oldStatus !== 'liverer') {
            \Log::info('Tentative d\'envoi d\'email pour la commande: ' . $commande->id);
            try {
                $commande->user->notify(new CommandeLivreeNotification($commande));
                \Log::info('Email envoyé avec succès pour la commande: ' . $commande->id);
            } catch (\Exception $e) {
                \Log::error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
            }
        }

        // Mise à jour des produits si fournis
        if (isset($validatedData['produits'])) {
            \Log::info('Mise à jour des produits pour la commande: ' . $commande->id);
            Commande_produit::where('commande_id', $commande->id)->delete();

            foreach ($validatedData['produits'] as $produit) {
                Commande_produit::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $produit['produit_id'],
                    'quantite' => $produit['quantite'],
                    'prix_unitaire' => $produit['prix_unitaire'],
                ]);
            }
            \Log::info('Produits mis à jour avec succès');
        }

        return response()->json([
            'message' => 'Commande mise à jour avec succès',
            'commande' => $commande->fresh()->load('produits')
        ], 200);
    }

    /**
     * Supprime une commande.
     */
    public function destroy($id)
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $commande = Commande::find($id);

        if (!$commande) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        $commande->delete();

        return response()->json(['message' => 'Commande supprimée avec succès'], 200);
    }
}
