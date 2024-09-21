<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommandeRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette demande.
     */
    public function authorize(): bool
    {
        // Par exemple, autoriser seulement les utilisateurs connectés
        return true;
    }

    /**
     * Règles de validation pour la création d'une commande.
     */
    public function rules(): array
    {
        return [
            'produits' => 'required|array',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|integer|min:1',
            'produits.*.prix_unitaire' => 'required|numeric|min:0',
            'somme' => 'required|numeric|min:0',
            'status' => 'required|in:en attente,liverer',
            'date' => 'required|date',
        ];
    }
    public function messages(): array{
        return [
            'produits.*.produit_id.required' => 'Le champ id du produit est requis pour chaque produit dans la commande.',
            'produits.*.produit_id.exists' => 'Le produit avec l\'ID :attribute n\'existe pas.',
            'produits.*.quantite.required' => 'Le champ quantité est requis pour chaque produit dans la commande.',
            'produits.*.quantite.integer' => 'Le champ quantité doit être un entier.',
            'produits.*.quantite.min' => 'Le champ quantité doit être au moins 1.',
            'produits.*.prix_unitaire.required' => 'Le champ prix unitaire est requis pour chaque produit dans la commande.',
        ];
    }
}
