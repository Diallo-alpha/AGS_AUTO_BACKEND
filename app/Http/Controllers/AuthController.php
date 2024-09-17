<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Inscription
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom_complet' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'telephone' => 'required|string|max:15',
            'photo' => 'nullable|image|mimes:jpeg,png|max:12077',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Création d'un nouvel utilisateur
        $user = User::create([
            'nom_complet' => $request->nom_complet,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telephone' => $request->telephone,
            'photo' => null, // Pas de photo par défaut
            'role' => 'client',
        ]);

        // Gestion de l'image uploadée
        if ($request->hasFile('photo')) {
            $imagePath = $request->file('photo')->store('photos', 'public');
            $user->photo = $imagePath;
        }

        // Sauvegarde de l'utilisateur
        $user->save();

        // Génération du token JWT
        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user', 'token'));
    }

    // Connexion
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => $user,
        ]);
    }

    // Déconnexion
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    // Rafraîchir le token
    public function refresh()
    {
        $newToken = auth()->refresh();

        return $this->respondWithToken($newToken);
    }

    // Répondre avec un token JWT
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }

    // Mise à jour du profil utilisateur
    public function update(Request $request)
    {
        // Journaliser le contenu JSON brut de la requête
        \Log::info('Contenu JSON brut de la requête:', [
            'json' => $request->getContent(),
        ]);

        // Décoder manuellement le JSON
        $jsonData = json_decode($request->getContent(), true);

        // Vérifier si les données JSON décodées sont nulles
        if (is_null($jsonData)) {
            \Log::error('Données JSON décodées sont nulles ou invalides');
            return response()->json([
                'error' => 'Données JSON invalides',
            ], 400);
        }

        // Vérifier si le JSON est valide
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error('Erreur de décodage JSON:', [
                'error' => json_last_error_msg(),
            ]);
            return response()->json([
                'error' => 'Données JSON invalides',
                'details' => json_last_error_msg()
            ], 400);
        }

        // Utiliser les données JSON décodées pour la validation
        $validator = Validator::make($jsonData, [
            'nom_complet' => 'sometimes|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
            'telephone' => 'sometimes|string|max:15',
            'photo' => 'nullable|image|mimes:jpeg,png|max:12077',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth()->user();

        // Vérifier s'il y a des changements
        $changements = array_intersect_key($jsonData, array_flip(['nom_complet', 'telephone', 'password']));

        if (empty($changements)) {
            return response()->json([
                'message' => 'Aucune donnée valide reçue pour la mise à jour',
                'donnees_recues' => $jsonData
            ], 400);
        }

        // Appliquer les changements
        foreach ($changements as $cle => $valeur) {
            if ($cle === 'password') {
                $user->password = Hash::make($valeur);
            } else {
                $user->$cle = $valeur;
            }
        }

        try {
            $user->save();
            return response()->json([
                'message' => 'Profil mis à jour avec succès',
                'user' => $user->fresh(),
                'changements' => $changements
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du profil: ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la mise à jour du profil'], 500);
        }
    }

    // Suppression d'un utilisateur
    public function delete()
    {
        $user = auth()->user();

        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->delete();

        return response()->json(['message' => 'Compte supprimé avec succès']);
    }
}
