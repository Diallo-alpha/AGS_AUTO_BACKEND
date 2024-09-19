<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateVideoRequest extends FormRequest
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
            'titre' => 'sometimes|string|max:255',
             'video' => 'sometimes|file|mimes:mp4,avi,flv,mov,wmv|max:1048576',
            'formation_id' => 'sometimes|required|exists:formations,id',
            'ressource_id' => 'sometimes|required|exists:ressources,id',
        ];
    }
    public function messages(): array{
        return [
            'titre.sometimes' => 'Le titre est obligatoire.',
            'titre.string' => 'Le titre doit être une chaîne de caractères.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            // 'video.sometimes' => 'La vidéo est obligatoire.',
            // 'video.mimes' => 'Le format de la vidéo doit être un format valide (MP4, MOV, AVI, WMV).',
            // 'video.max' => 'La vidéo ne doit pas dépasser 70Mo.',
            'formation_id.sometimes' => 'La formation est obligatoire.',
            'formation_id.required' => 'Cette formation n\'existe pas.',
            'formation_id.exists' => 'Cette formation n\'exist pas',
            'ressource_id.sometimes' => 'La ressource est obligatoire.',
            'ressource_id.required' => 'Cette ressource n\'existe pas.',
            'ressource_id.exists' => 'Cette ressource n\'existe pas.',
        ];
    }
    public function failedValidation(Validator $validator){
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'errors'      => $validator->errors()
        ], 422));
    }
}
