<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->hash_id,
            'company'                => $this->company,
            'role'                   => $this->role,
            'job_url'                => $this->job_url,
            'location'               => $this->location,
            'work_type'              => $this->work_type,
            'employment_type'        => $this->employment_type,
            'status'                 => $this->status,
            'priority'               => $this->priority,
            'applied_date'           => $this->applied_date,
            'deadline'               => $this->deadline,
            'salary_min'             => $this->salary_min,
            'salary_max'             => $this->salary_max,
            'salary_currency'        => $this->salary_currency,
            'notes'                  => $this->notes,
            'archived_at'            => $this->archived_at,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
            'interview_rounds_count' => $this->whenCounted('interviewRounds'),
            'contacts'               => $this->whenLoaded('contacts'),
            'interview_rounds'       => $this->whenLoaded('interviewRounds'),
        ];
    }
}
