<?php

namespace App\Http\Requests\Api\V1\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Koordinat latitude wajib dikirim.',
            'latitude.between' => 'Nilai latitude harus berada di antara -90 dan 90.',
            'longitude.required' => 'Koordinat longitude wajib dikirim.',
            'longitude.between' => 'Nilai longitude harus berada di antara -180 dan 180.',
            'accuracy_meters.required' => 'Nilai akurasi GPS wajib dikirim.',
            'accuracy_meters.gt' => 'Nilai akurasi GPS harus lebih besar dari 0.',
        ];
    }
}
