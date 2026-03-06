<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'order_number' => $this->order_number,
            'status'       => $this->status,
            'order_date'   => $this->order_date?->format('Y-m-d'),
            'shipped_date' => $this->shipped_date?->format('Y-m-d'),
            'total_amount' => $this->total_amount,
            'notes'        => $this->notes,
            'customer_id'  => $this->customer_id,
            'employee_id'  => $this->employee_id,
            'warehouse_id' => $this->warehouse_id,
            'customer'     => new CustomerResource($this->whenLoaded('customer')),
            'employee'     => new EmployeeResource($this->whenLoaded('employee')),
            'warehouse'    => new WarehouseResource($this->whenLoaded('warehouse')),
            'items'        => TransactionItemResource::collection($this->whenLoaded('items')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
