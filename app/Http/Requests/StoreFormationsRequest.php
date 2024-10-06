<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreFormationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_formation' => 'required|string|max:255',
            'description' => 'required|string',
            'prix' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'nom_formation.required' => 'Le nom de la formation est obligatoire.',
            'nom_formation.string' => 'Le nom de la formation doit être une chaîne de caractères.',
            'nom_formation.max' => 'Le nom de la formation ne doit pas dépasser 255 caractères.',
            'description.required' => 'La description de la formation est obligatoire.',
            'description.string' => 'La description de la formation doit être une chaîne de caractères.',
            'prix.required' => 'Le prix de la formation est obligatoire.',
            'prix.integer' => 'Le prix de la formation doit être un nombre entier.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être de type : jpeg, png, jpg, gif.',
            'image.max' => 'L\'image ne doit pas dépasser 2Mo.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        Log::error('Erreurs de validation:', $validator->errors()->toArray());
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'errors'      => $validator->errors()
        ], 422));
    }
}
