<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterviewRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'             => 'nullable|date',
            'type'             => 'nullable|in:technical,hr,system_design,take_home',
            'interviewer_name' => 'nullable|string|max:255',
            'notes'            => 'nullable|string',
            'self_rating'      => 'nullable|integer|min:1|max:5',
        ];
    }
}
