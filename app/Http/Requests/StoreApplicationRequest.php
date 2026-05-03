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
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'company'           => $isUpdate ? 'sometimes|string|max:255'   : 'required|string|max:255',
            'role'              => $isUpdate ? 'sometimes|string|max:255'   : 'required|string|max:255',
            'job_url'           => 'nullable|url|max:500',
            'location'          => 'nullable|string|max:255',
            'work_type'         => 'nullable|in:remote,onsite,hybrid',
            'employment_type'   => 'nullable|in:full_time,part_time,contract,internship,freelance',
            'status'            => 'sometimes|in:wishlist,applied,phone_screen,interview,offer,rejected,archived',
            'priority'          => 'sometimes|in:high,medium,low',
            'applied_date'      => 'nullable|date',
            'deadline'          => 'nullable|date',
            'salary_min'        => 'nullable|numeric|min:0',
            'salary_max'        => 'nullable|numeric|min:0|gte:salary_min',
            'notes'             => 'nullable|string|max:5000',
        ];
    }
}
