<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePartenaireRequest extends FormRequest
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
            "nom_partenaire" => "required|string|max:255",
            "logo" => "required|image|mimes:jpg,jpeg,png|max:1024",
            "email" => "required|email|unique:partenaires,email",
            "telephone" => "required|string|unique:partenaires,telephone",
        ];
    }
    public function messages(): array{
        return [
            "nom_partenaire.required" => "Le nom du partenaire est requis.",
            "nom_partenaire.string" => "Le nom du partenaire doit être une chaîne de caractères.",
            "nom_partenaire.max" => "Le nom du partenaire ne doit pas dépasser 255 caractères.",
            "logo.required" => "Le logo est obligatoire.",
            "logo.image" => "Le format du logo doit être une image.",
            "logo.mimes" => "Le format du logo doit être JPG, JPEG ou PNG.",
            "logo.max" => "Le logo ne doit pas dépasser 1024 Ko.",
            "email.required" => "L'email est obligatoire.",
            "email.email" => "L'email doit être une adresse email valide.",
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
