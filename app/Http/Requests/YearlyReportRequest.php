<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class YearlyReportRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $rules = [
            'year_id' => ['required', 'exists:years,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'in:draft,published'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'], // 10MB max
            'created_by' => ['required', 'string', 'max:100']
        ];

        // Add unique validation for create/update
        if ($this->isMethod('POST')) {
            // For creating new reports
            $rules['year_id'][] = Rule::unique('yearly_reports');
        } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            // For updating existing reports
            $rules['year_id'][] = Rule::unique('yearly_reports')->ignore($this->route('id'));
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'year_id.required' => 'Please select a year.',
            'year_id.exists' => 'The selected year is invalid.',
            'year_id.unique' => 'A yearly report already exists for this year.',
            'title.string' => 'Title must be a valid text.',
            'title.max' => 'Title cannot exceed 255 characters.',
            'description.string' => 'Description must be a valid text.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'status.in' => 'Status must be either draft or published.',
            'file.file' => 'Please upload a valid file.',
            'file.mimes' => 'File must be a PDF, DOC, DOCX, XLS, or XLSX file.',
            'file.max' => 'File size cannot exceed 10MB.',
            'created_by.required' => 'Created by field is required.',
            'created_by.string' => 'Created by must be a valid text.',
            'created_by.max' => 'Created by cannot exceed 100 characters.'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'year_id' => 'year',
            'created_by' => 'creator name'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        if ($this->expectsJson()) {
            $response = response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
            
            throw new \Illuminate\Validation\ValidationException($validator, $response);
        }
        
        parent::failedValidation($validator);
    }
}
