<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreNoteFormationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'formation_id' => 'required|exists:formations,id',
            'note' => 'required|integer|min:1|max:5',
            'avis' => 'nullable|string|max:1000',
        ];
    }
    public function messages(): array{
        return [
            'formation_id.required' => 'La formation est obligatoire.',
            'formation_id.exists' => 'La formation n\'existe pas.',
            'note.required' => 'La note est obligatoire.',
            'note.integer' => 'La note doit être un entier.',
            'note.min' => 'La note doit être au minimum 1.',
            'note.max' => 'La note doit être au maximum 5.',
            'avis.string' => 'L\'avis doit être une chaîne de caractères.',
            'avis.max' => 'L\'avis ne doit pas dépasser 1000 caractères.',
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
