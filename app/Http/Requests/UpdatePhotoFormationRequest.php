<?php
namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePhotoFormationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_photo' => 'sometimes|string|max:255',
            'photo' => 'sometimes|image|mimes:jpg,jpeg,png|max:22077',
            'formation_id' => 'sometimes|required|exists:formations,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nom_photo.sometimes' => 'Le nom de la photo est obligatoire.',
            'nom_photo.string' => 'Le nom de la photo doit être une chaîne de caractères.',
            'nom_photo.max' => 'Le nom de la photo ne doit pas dépasser 255 caractères.',
            'photo.image' => 'Le fichier doit être une image valide (jpg, jpeg, png).',
            'formation_id.sometimes' => 'L\'ID de la formation est obligatoire.',
            'formation_id.exists' => 'L\'ID de la formation n\'existe pas dans la base de données.',
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
