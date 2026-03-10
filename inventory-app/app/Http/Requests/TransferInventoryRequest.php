<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class TransferInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::INVENTORY_TRANSFER) ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id'        => 'required|exists:products,id',
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id'   => 'required|exists:warehouses,id',
            'quantity'          => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'from_warehouse_id.different' => 'Gudang asal dan tujuan tidak boleh sama.',
        ];
    }
}
