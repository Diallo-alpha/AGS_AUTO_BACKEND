<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePanierRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'item_id' => 'sometimes|required|integer',
            'item_type' => 'sometimes|required|in:produit,formation',
            'quantity' => 'sometimes|required|integer|min:1'
        ];
    }
    public function messages(): array{
        return [
            'item_id.required' => 'L\'identifiant de l\'article est requis.',
            'item_id.integer' => 'L\'identifiant de l\'article doit être un entier.',
            'item_type.required' => 'Le type de l\'article est requis.',
            'item_type.in' => 'Le type de l\'article doit être soit "produit" soit "formation".',
            'quantity.required' => 'La quantité de l\'article est requise.',
            'quantity.integer' => 'La quantité de l\'article doit être un entier.',
            'quantity.min' => 'La quantité de l\'article doit être supérieure ou égale à 1.',
        ];
    }
}
