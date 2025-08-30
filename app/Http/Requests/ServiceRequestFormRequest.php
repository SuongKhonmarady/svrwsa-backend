<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceRequestFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow all users to submit service requests
    }    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'service_type' => 'required|string|max:255',
            'details' => 'nullable|string|max:2000',
            
            // New required fields
            'family_members' => 'required|integer|min:1',
            'female_members' => 'required|integer|min:0',
            'village' => 'required|string|max:255',
            'commune_id' => 'required|exists:communes,id',
            'district_id' => 'required|exists:districts,id',
            'province_id' => 'required|exists:provinces,id',
            'occupation_id' => 'required|exists:occupations,id',
            'usage_type_id' => 'required|exists:usage_types,id',
            
            // Document upload validation
            'id_card' => 'required|array|size:2',
            'id_card.*' => 'file|mimes:jpg,jpeg,png|max:5120',
            
            'family_book' => 'required|array|min:1',
            'family_book.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.email' => 'The email must be a valid email address.',
            'service_type.required' => 'The service type field is required.',
            
            // Document validation messages
            'id_card.required' => 'ID card images are required.',
            'id_card.size' => 'Exactly 2 ID card images (front and back) are required.',
            'id_card.*.file' => 'Each ID card must be a valid file.',
            'id_card.*.mimes' => 'ID card images must be in JPG, JPEG, or PNG format.',
            'id_card.*.max' => 'Each ID card image must not exceed 2MB.',
            
            'family_book.required' => 'Family book images are required.',
            'family_book.min' => 'At least one family book image is required.',
            'family_book.*.file' => 'Each family book file must be a valid file.',
            'family_book.*.mimes' => 'Family book files must be in JPG, JPEG, PNG, or PDF format.',
            'family_book.*.max' => 'Each family book file must not exceed 2MB.',
        ];
    }
}
