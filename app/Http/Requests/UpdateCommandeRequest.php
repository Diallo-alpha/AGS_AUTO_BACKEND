<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCommandeRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette demande.
     */
    public function authorize(): bool
    {
        //return auth()->check();
        return true;
    }

    /**
     * Règles de validation pour la mise à jour d'une commande.
     */
    public function rules(): array
    {
        return [
            'produits' => 'sometimes|array',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|integer|min:1',
            'produits.*.prix_unitaire' => 'required|numeric|min:0',
            'somme' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:en attente,liverer',
            'date' => 'sometimes|date',
        ];
    }
    /**
     * Custom error messages pour la validation.
     */
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
    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json([
           'success' => false,
            'errors' => $validator->errors()
        ], 422));
    }
}
