<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'sku'                => $this->sku,
            'name'               => $this->name,
            'description'        => $this->description,
            'standard_cost'      => $this->standard_cost,
            'list_price'         => $this->list_price,
            'profit_margin'      => $this->profit_margin,
            'profit_percentage'  => $this->profit_percentage,
            'is_active'          => $this->is_active,
            'category'           => new CategoryResource($this->whenLoaded('category')),
            'inventories'        => InventoryResource::collection($this->whenLoaded('inventories')),
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
