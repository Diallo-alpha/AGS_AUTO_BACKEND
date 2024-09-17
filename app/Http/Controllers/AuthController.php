<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;


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
            'role' => 'client',
        ]);

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
}

