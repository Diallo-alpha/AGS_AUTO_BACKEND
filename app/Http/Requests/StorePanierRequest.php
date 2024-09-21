<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StorePanierRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'item_id' => 'required|integer',
            'item_type' => 'required|in:produit,formation',
            'quantity' => 'required|integer|min:1'
        ];
    }
    public function messages(): array {
        return [
            'item_id.required' => 'L\'id de l\'item est requis',
            'item_type.required' => 'Le type de l\'item est requis',
            'item_type.in' => 'Le type de l\'item doit être soit "produit" soit "formation"',
            'quantity.required' => 'La quantité est requise',
            'quantity.integer' => 'La quantité doit être un nombre entier',
            'quantity.min' => 'La quantité doit être au minimum 1'
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
