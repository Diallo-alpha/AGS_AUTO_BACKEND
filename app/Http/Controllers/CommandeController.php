<?php

namespace App\Http\Controllers;

use Log;
use App\Models\User;
use App\Models\Commande;
use App\Models\Commande_produit;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreCommandeRequest;
use App\Http\Requests\UpdateCommandeRequest;
use App\Notifications\CommandeLivreeNotification;
use App\Notifications\CommandeRecueNotification;

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
        // Valider les données de la requête
        $validatedData = $request->validated();

        try {
            // Créer la commande
            $commande = Commande::create([
                'user_id' => Auth::id(),
                'somme' => $validatedData['somme'],
                'status' => $validatedData['status'],
                'date' => $validatedData['date'],
            ]);

            // Insérer les produits dans la table pivot
            foreach ($validatedData['produits'] as $produit) {
                Commande_produit::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $produit['produit_id'],
                    'quantite' => $produit['quantite'],
                    'prix_unitaire' => $produit['prix_unitaire'],
                ]);
            }

            // Envoyer la notification aux administrateurs avec gestion des erreurs
            try {
                $admins = User::role('admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new CommandeRecueNotification($commande));
                }
            } catch (\Exception $e) {
                // Journaliser l'erreur si l'envoi de la notification échoue
                \Log::error('Erreur lors de l\'envoi des notifications aux administrateurs : ' . $e->getMessage());
                // Optionnel : vous pouvez ajouter un message d'erreur personnalisé ici
            }

            // Retourner une réponse de succès
            return response()->json(['message' => 'Commande créée avec succès'], 201);
        } catch (\Exception $e) {
            // Journaliser l'erreur et retourner une réponse 500 en cas d'échec de la création de la commande
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
        \Log::info('Début de la mise à jour de la commande:', ['id' => $id]);

        // Vérification admin
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            \Log::warning('Tentative d\'accès non autorisée:', [
                'user_id' => auth()->id(),
                'roles' => auth()->user() ? auth()->user()->roles : 'non connecté'
            ]);
            return response()->json(['message' => 'Accès refusé - Réservé aux administrateurs'], 403);
        }

        // Récupération de la commande
        $commande = Commande::with('user')->find($id);
        if (!$commande) {
            \Log::info('Commande non trouvée:', ['id' => $id]);
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        // Vérification des données utilisateur
        \Log::info('Données utilisateur de la commande:', [
            'commande_id' => $commande->id,
            'user_id' => $commande->user_id,
            'user_email' => $commande->user->email ?? 'Email manquant',
            'user_exists' => isset($commande->user),
        ]);

        // Validation des données
        $validatedData = $request->validated();
        \Log::info('Données validées reçues:', $validatedData);

        $oldStatus = $commande->status;
        \Log::info('Status de la commande:', [
            'ancien' => $oldStatus,
            'nouveau' => $validatedData['status'] ?? $oldStatus
        ]);

        // Mise à jour de la commande
        try {
            $commande->update([
                'somme' => $validatedData['somme'] ?? $commande->somme,
                'status' => $validatedData['status'] ?? $commande->status,
                'date' => $validatedData['date'] ?? $commande->date,
            ]);
            \Log::info('Commande mise à jour avec succès');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour de la commande:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['message' => 'Erreur lors de la mise à jour'], 500);
        }

        // Vérification et envoi de la notification
        if (($validatedData['status'] ?? $oldStatus) === 'liverer' && $oldStatus !== 'liverer') {
            \Log::info('Tentative d\'envoi de notification:', [
                'commande_id' => $commande->id,
                'user_id' => $commande->user_id,
                'user_email' => $commande->user->email ?? 'Email manquant'
            ]);

            try {
                // Vérification de la configuration mail
                \Log::info('Configuration mail:', [
                    'driver' => config('mail.driver'),
                    'host' => config('mail.host'),
                    'port' => config('mail.port'),
                    'from_address' => config('mail.from.address'),
                ]);

                // Vérification que l'utilisateur peut recevoir des notifications
                if (!$commande->user || !method_exists($commande->user, 'notify')) {
                    throw new \Exception('Utilisateur non notifiable');
                }

                $commande->user->notify(new CommandeLivreeNotification($commande));
                \Log::info('Notification envoyée avec succès');
            } catch (\Exception $e) {
                \Log::error('Erreur lors de l\'envoi de la notification:', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Mise à jour des produits
        if (isset($validatedData['produits'])) {
            try {
                \Log::info('Début de la mise à jour des produits');
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
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la mise à jour des produits:', [
                    'error' => $e->getMessage()
                ]);
            }
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
