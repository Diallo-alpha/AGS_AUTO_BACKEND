<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCommentaireRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom_complet' => 'sometimes|string|max:255',
            'contenu' => 'sometimes|string|min:3',
        ];
    }
    public function messages(): array{
        return [
            'nom_complet.string' => 'Le nom complet doit être une chaîne de caractères.',
            'nom_complet.max' => 'Le nom complet ne doit pas dépasser 255 caractères.',
            'contenu.string' => 'Le contenu doit être une chaîne de caractères.',
            'contenu.min' => 'Le contenu doit contenir au moins 3 caractères.',
        ];
    }
    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json([
           'success' => false,
            'errors' => $validator->errors()
        ], 422));
    }
}
