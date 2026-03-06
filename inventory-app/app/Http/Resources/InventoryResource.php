<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'product_id'          => $this->product_id,
            'warehouse_id'        => $this->warehouse_id,
            'qty_on_hand'         => $this->qty_on_hand,
            'qty_reserved'        => $this->qty_reserved,
            'qty_available'       => $this->qty_available,
            'min_stock'           => $this->min_stock,
            'max_stock'           => $this->max_stock,
            'is_low_stock'        => $this->is_low_stock,
            'last_restocked_at'   => $this->last_restocked_at,
            'product'             => new ProductResource($this->whenLoaded('product')),
            'warehouse'           => new WarehouseResource($this->whenLoaded('warehouse')),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
