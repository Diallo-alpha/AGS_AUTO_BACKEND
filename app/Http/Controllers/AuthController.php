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
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom_complet' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'telephone' => 'required|string|max:15|unique:users',
            'photo' => 'nullable|image|mimes:jpeg,png|max:12077',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'nom_complet' => $request->nom_complet,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telephone' => $request->telephone,
            'role' => 'client', // On définit le rôle par défaut
            'photo' => null,
        ]);

        // On assigne également le rôle avec Spatie
        $user->assignRole('client');

        if ($request->hasFile('photo')) {
            $imagePath = $request->file('photo')->store('photos', 'public');
            $user->photo = $imagePath;
            $user->save();
        }

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user', 'token'));
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        $roles = $user->getRoleNames(); // Méthode de Spatie pour obtenir les noms des rôles

        Log::info('Utilisateur connecté', [
            'user' => $user->email,
            'roles' => $roles,
        ]);

        return $this->respondWithToken($token);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60 * 3, // 3 heures
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request)
    {
        $jsonData = json_decode($request->getContent(), true);

        if (is_null($jsonData) || json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'error' => 'Données JSON invalides',
                'details' => json_last_error_msg()
            ], 400);
        }

        $validator = Validator::make($jsonData, [
            'nom_complet' => 'sometimes|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
            'telephone' => 'sometimes|string|max:15|unique:users,telephone,' . auth()->id(),
            'photo' => 'nullable|image|mimes:jpeg,png|max:12077',
            'role' => 'sometimes|string|in:client,admin',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth()->user();
        $changements = array_intersect_key($jsonData, array_flip(['nom_complet', 'telephone', 'password', 'role']));

        if (empty($changements)) {
            return response()->json([
                'message' => 'Aucune donnée valide reçue pour la mise à jour',
                'donnees_recues' => $jsonData
            ], 400);
        }

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
