<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserFormationRequest extends FormRequest
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
            'formation_id' => 'required|exists:formations,id',
            'user_id' => 'required|exists:users,id',
        ];
    }

    public function messages()
    {
        return [
            'formation_id.required' => 'La formation est requise.',
            'formation_id.exists' => 'La formation spécifiée n\'existe pas.',
            'user_id.required' => 'L\'utilisateur est requis.',
            'user_id.exists' => 'L\'utilisateur spécifié n\'existe pas.',
        ];
    }

    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json([
           'success' => false,
            'errors' => $validator->errors()
        ], 422));
    }
}
