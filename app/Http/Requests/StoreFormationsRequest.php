<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreFormationsRequest extends FormRequest
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
            'nom_formation' => 'required|string|max:255',
            'description' => 'required|string',
            'prix' => 'required|integer',
        ];
    }

    public function messages():array{
        return [
            'nom_formation.required' => 'Le nom de la formation est obligatoire.',
            'nom_formation.string' => 'Le nom de la formation doit être une chaîne de caractères.',
            'nom_formation.max' => 'Le nom de la formation ne doit pas dépasser 255 caractères.',
            'description.required' => 'La description de la formation est obligatoire.',
            'description.string' => 'La description de la formation doit être une chaîne de caractères.',
            'prix.required' => 'Le prix de la formation est obligatoire.',
            'prix.integer' => 'Le prix de la formation doit être un nombre entier.',
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
