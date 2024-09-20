<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreserviceRequest extends FormRequest
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
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'partenaire_id' => 'required|exists:partenaires,id'
        ];
    }
    public function messages(): array{
        return [
            'titre.required' => 'Le champ titre est obligatoire.',
            'titre.string' => 'Le champ titre doit être une chaîne de caractères.',
            'titre.max' => 'Le champ titre doit contenir au maximum 255 caractères.',
            'description.required' => 'Le champ description est obligatoire.',
            'description.string' => 'Le champ description doit être une chaîne de caractères.',
            'photo.required' => 'Le champ photo est obligatoire.',
            'photo.image' => 'Le champ photo doit être une image.',
            'photo.mimes' => 'Le champ photo doit être au format jpg, png ou jpeg.',
            'photo.max' => 'La taille de l\'image ne doit pas dépasser 2MB.',
            'partenaire_id' => 'ajouter le partenaire lier a cette service.',
        ];
    }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422));
    }
}
