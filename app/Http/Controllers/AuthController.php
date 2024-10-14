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
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:12077',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'nom_complet' => $request->nom_complet,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telephone' => $request->telephone,
            'role' => 'client',
            'photo' => null,
        ]);

        $user->assignRole('client');

        if ($request->hasFile('photo')) {
            $imagePath = $request->file('photo')->store('photos', 'public');
            $user->photo = $imagePath;
            $user->save();
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Utilisateur enregistré avec succès',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        $user = auth()->user();
        $roles = $user->getRoleNames();

        Log::info('Utilisateur connecté', [
            'user' => $user->email,
            'roles' => $roles,
        ]);

        return $this->respondWithToken($token);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        $user = auth()->user();
        $userData = $user->toArray();

        $userData['profile_picture'] = $user->photo
            ? asset('storage/' . $user->photo)
            : asset('storage/photos/default-profile-pic.png');

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60 * 3,
            'user' => $userData,
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'nom_complet' => 'sometimes|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
            'telephone' => 'sometimes|string|max:15|unique:users,telephone,' . $user->id,
            'role' => 'sometimes|string|in:client,admin',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $fieldsToUpdate = ['nom_complet', 'telephone', 'role'];
        foreach ($fieldsToUpdate as $field) {
            if ($request->has($field)) {
                $user->$field = $request->$field;
            }
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        try {
            $user->save();
            return response()->json([
                'message' => 'Profil mis à jour avec succès',
                'user' => $user->fresh()
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

    public function updateProfilePicture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:12077',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth()->user();

        if ($request->hasFile('photo')) {
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            $imagePath = $request->file('photo')->store('photos_profil', 'public');
            $user->photo = $imagePath;
            $user->save();

            $photoUrl = asset('storage/' . $imagePath);

            return response()->json([
                'message' => 'Photo de profil mise à jour avec succès',
                'photo_url' => $photoUrl
            ]);
        }

        return response()->json(['error' => 'Aucun fichier n\'a été uploadé'], 400);
    }

    public function getUserInfo()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $userInfo = [
            'id' => $user->id,
            'nom_complet' => $user->nom_complet,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'role' => $user->role,
            'photo' => $user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/default-profile-pic.png'),
        ];

        return response()->json($userInfo);
    }
}
