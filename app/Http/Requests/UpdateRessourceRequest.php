<?php

namespace App\Http\Requests;


use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateRessourceRequest extends FormRequest
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
            'documents' => 'sometimes|required|mimes:pdf,docx,xlsx,txt,jpg,jpeg,png',
        ];
    }
    public function messages(): array{
        return [
            'titre.required' => 'Le titre est requis',
            'titre.string' => 'Le titre doit être une chaîne de caractères',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères',
            'documents.required' => 'Le document est requis',
            'documents.mimes' => 'Le format du document doit être un des suivants: pdf, docx, xlsx, txt, jpg, jpeg, png',
        ];
    }
    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422));
    }
}
