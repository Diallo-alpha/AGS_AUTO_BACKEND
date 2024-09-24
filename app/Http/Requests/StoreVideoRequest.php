<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreVideoRequest extends FormRequest
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
            'titre' => 'required|string|max:255',
            // 'video' => 'required|file|mimes:mp4,avi,flv,mov,wmv,webm,webp|max:1048576',
            'formation_id' => 'required|exists:formations,id',

        ];
    }
    public function messages(): array{
        return [
            'titre.required' => 'Le titre est obligatoire.',
            'titre.string' => 'Le titre doit être une chaîne de caractères.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'video.required' => 'La vidéo est obligatoire.',
            'video.mimetypes' => 'Le format de la vidéo doit être un format valide (MP4, MOV, AVI, WMV).',
            'video.max' => 'La vidéo ne doit pas dépasser 70Mo.',
            'formation_id.required' => 'La formation est obligatoire.',
            'formation_id.exists' => 'Cette formation n\'existe pas.',
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
