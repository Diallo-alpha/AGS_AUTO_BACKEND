<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategorieRequest extends FormRequest
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
            'nom_categorie' => 'required|string|max:255|unique:categories,nom_categorie',
        ];
    }

    /**
     * Custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'nom_categorie.required' => 'Le nom de la catégorie est requis.',
            'nom_categorie.string' => 'Le nom de la catégorie doit être une chaîne de caractères.',
            'nom_categorie.max' => 'Le nom de la catégorie ne doit pas dépasser 255 caractères.',
            'nom_categorie.unique' => 'Cette catégorie existe déjà.',
        ];
    }
}
