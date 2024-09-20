<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateserviceRequest extends FormRequest
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
            'titre' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'photo' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'partenaire_id' => 'sometimes|exists:partenaires,id'
        ];
    }
    public function messages(): array{
        return [
            'titre.sometimes' => 'Le champ titre est optionnel.',
            'titre.string' => 'Le champ titre doit être une chaîne de caractères.',
            'titre.max' => 'Le champ titre doit contenir au maximum 255 caractères.',
            'description.sometimes' => 'Le champ description est optionnel.',
            'description.string' => 'Le champ description doit être une chaîne de caractères.',
            'photo.sometimes' => 'Le champ photo est optionnel.',
            'photo.image' => 'Le champ photo doit être une image.',
            'photo.mimes' => 'Le champ photo doit être au format jpg, png ou jpeg.',
            'photo.max' => 'La taille de l\'image ne doit pas dépasser 2MB.',
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
