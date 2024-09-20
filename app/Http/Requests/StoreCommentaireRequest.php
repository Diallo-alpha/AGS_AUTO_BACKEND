<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCommentaireRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Autoriser tous les utilisateurs à commenter
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nom_complet' => 'required|string|max:255',
            'contenu' => 'required|string|min:3',
            'article_id' => 'required|exists:articles,id', // Valider que l'article existe
        ];
    }
    public function messages(): array{
        return [
            'nom_complet.required' => 'Le nom complet est obligatoire.',
            'nom_complet.string' => 'Le nom complet doit être une chaîne de caractères.',
            'nom_complet.max' => 'Le nom complet ne doit pas dépasser 255 caractères.',
            'contenu.required' => 'Le contenu est obligatoire.',
            'contenu.string' => 'Le contenu doit être une chaîne de caractères.',
            'contenu.min' => 'Le contenu doit contenir au moins 3 caractères.',
            'article_id.required' => 'L\'article est obligatoire.',
            'article_id.exists' => 'L\'article n\'existe pas.',
        ];
    }
    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json([
           'success' => false,
            'errors' => $validator->errors()
        ], 422));
    }
}

