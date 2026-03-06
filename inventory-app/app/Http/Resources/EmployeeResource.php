<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'job_title'    => $this->job_title,
            'department'   => $this->department,
            'hire_date'    => $this->hire_date?->format('Y-m-d'),
            'is_active'    => $this->is_active,
            'warehouse_id' => $this->warehouse_id,
            'warehouse'    => new WarehouseResource($this->whenLoaded('warehouse')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
