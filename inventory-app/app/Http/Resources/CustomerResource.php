<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'address'             => $this->address,
            'credit_limit'        => $this->credit_limit,
            'credit_used'         => $this->credit_used,
            'credit_available'    => $this->credit_available,
            'status'              => $this->status,
            'transactions_count'  => $this->whenCounted('transactions'),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
