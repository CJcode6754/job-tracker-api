<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company'      => 'required|string|max:255',
            'role'         => 'required|string|max:255',
            'job_url'      => 'nullable|url|max:500',
            'status'       => 'required|in:wishlist,applied,phone_screen,interview,offer,rejected',
            'priority'     => 'required|in:high,medium,low',
            'applied_date' => 'nullable|date',
            'deadline'     => 'nullable|date',
            'salary_min'   => 'nullable|numeric|min:0',
            'salary_max'   => 'nullable|numeric|min:0|gte:salary_min',
            'notes'        => 'nullable|string',
        ];
    }
}
