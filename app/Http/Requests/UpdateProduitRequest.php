<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProduitRequest extends FormRequest
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
            'image' => 'sometimes|required|image|mimes:jpg,jpeg,png|max:12077',
            'nom_produit' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'prix' => 'sometimes|required|numeric|min:0',
            'categorie_id' => 'sometimes|required|exists:categories,id',
        ];
    }

    /**
     * Custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'nom_produit.required' => 'Le nom du produit est requis.',
            'nom_produit.string' => 'Le nom du produit doit être une chaîne de caractères.',
            'nom_produit.max' => 'Le nom du produit ne doit pas dépasser 255 caractères.',
            'prix.required' => 'Le prix du produit est requis.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
            'categorie_id.required' => 'La catégorie est requise.',
            'categorie_id.exists' => 'La catégorie sélectionnée est invalide.',
            'image.required' => 'L\'image est obligatoire.',
            'image.image' => 'Le format de l\'image doit être une image.',
            'image.mimes' => 'Le format de l\'image doit être JPG, JPEG ou PNG.',
            'image.max' => 'L\'image ne doit pas dépasser 12077 Ko.',
        ];
    }
}
