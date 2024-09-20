<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateArticleRequest extends FormRequest
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
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'photo' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:10024',
        ];
    }
    /**
     * Custom error messages for validation.
     */
    public function messages(): array{
        return [
            'titre.sometimes' => 'Le champ titre est optionnel.',
            'titre.required' => 'Le champ titre est requis.',
            'titre.string' => 'Le champ titre doit être une chaîne de caractères.',
            'titre.max' => 'Le champ titre ne doit pas dépasser 255 caractères.',
            'description.required' => 'Le champ description est requis.',
            'description.string' => 'Le champ description doit être une chaîne de caractères.',
            'photo.image' => 'Le format de l\'image doit être une image.',
            'photo.mimes' => 'Le format de l\'image doit être JPG, JPEG ou PNG.',
            'photo.max' => 'L\'image ne doit pas dépasser 10024 Ko.',
        ];
    }
    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json([
           'success' => false,
            'errors' => $validator->errors()
        ], 422));
    }

}
