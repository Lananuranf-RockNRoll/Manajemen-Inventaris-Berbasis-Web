<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::PRODUCT_CREATE) ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id'   => 'required|exists:categories,id',
            'sku'           => 'required|string|max:50|unique:products,sku',
            'name'          => 'required|string|max:200',
            'description'   => 'nullable|string',
            'standard_cost' => 'required|numeric|min:0',
            'list_price'    => 'required|numeric|min:0|gte:standard_cost',
            'is_active'     => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique'         => 'SKU produk sudah digunakan.',
            'list_price.gte'     => 'Harga jual harus lebih besar atau sama dengan harga modal.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
        ];
    }
}
