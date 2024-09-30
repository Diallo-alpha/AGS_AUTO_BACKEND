<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateFormationsRequest extends FormRequest
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
            'nom_formation' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'prix' => 'sometimes|integer',
            'image' => 'nullable|sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'nom_formation.string' => 'Le nom de la formation doit être une chaîne de caractères.',
            'nom_formation.max' => 'Le nom de la formation ne doit pas dépasser 255 caractères.',
            'description.string' => 'La description de la formation doit être une chaîne de caractères.',
            'prix.integer' => 'Le prix de la formation doit être un nombre entier.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être de type : jpeg, png, jpg, gif.',
            'image.max' => 'L\'image ne doit pas dépasser 2Mo.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'errors'      => $validator->errors()
        ], 422));
    }
}
