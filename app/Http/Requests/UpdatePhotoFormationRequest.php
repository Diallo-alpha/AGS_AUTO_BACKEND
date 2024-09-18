<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePhotoFormationRequest extends FormRequest
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
            'nom_photo' => 'sometimes|required|string|max:255',
            'photo' => 'sometimes|required|string',
            'formation_id' => 'sometimes|required|exists:formations,id',
        ];
    }
    public function messages(): array
    {
        return [
            'nom_photo.required' => 'Le nom de la photo est obligatoire.',
            'nom_photo.string' => 'Le nom de la photo doit être une chaîne de caractères.',
            'nom_photo.max' => 'Le nom de la photo ne doit pas dépasser 255 caractères.',
            'photo.required' => 'La photo est obligatoire.',
            'photo.string' => 'Le format de la photo doit être une chaîne de caractères.',
            'formation_id.required' => 'L\'id de la formation est obligatoire.',
            'formation_id.exists' => 'L\'id de la formation n\'existe pas.',
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
