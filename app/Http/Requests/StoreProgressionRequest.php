<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProgressionRequest extends FormRequest
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
            'pourcentage' => 'required|integer|min:0|max:100',
        ];
    }
    public function messages(): array{
        return [
            'formation_id.required' => 'La formation est obligatoire.',
            'formation_id.exists' => 'La formation n\'existe pas.',
            'pourcentage.required' => 'Le pourcentage est obligatoire.',
            'pourcentage.integer' => 'Le pourcentage doit être un entier.',
            'pourcentage.min' => 'Le pourcentage doit être au minimum 0.',
            'pourcentage.max' => 'Le pourcentage doit être au maximum 100.',
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
