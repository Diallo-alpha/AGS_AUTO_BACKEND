<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartenaireRequest extends FormRequest
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
            "nom_partenaire" => "sometimes|required|string|max:255",
            "email" => "sometimes|required|string|max:255",
            "telephone" => "sometimes|required|string",
            'logo' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:1024',
        ];
    }
    /**
     * Custom error messages for validation.
     */
    public function messages(): array{
        return [
            "nom_partenaire.required" => "Le nom du partenaire est requis.",
            "nom_partenaire.string" => "Le nom du partenaire doit être une chaîne de caractères.",
            "nom_partenaire.max" => "Le nom du partenaire ne doit pas dépasser 255 caractères.",
            "email.required" => "L'email est requis.",
            "email.string" => "L'email doit être une chaîne de caractères.",
            "email.max" => "L'email ne doit pas dépasser 255 caractères.",
            "telephone.required" => "Le téléphone est requis.",
            "telephone.string" => "Le téléphone doit être une chaîne de caractères.",
        ];
    }
}
