<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'employee_code' => $this->employeeProfile?->employee_code,
            'join_date' => $this->employeeProfile?->join_date?->toDateString(),
            'address' => $this->employeeProfile?->address,
            'notes' => $this->employeeProfile?->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
