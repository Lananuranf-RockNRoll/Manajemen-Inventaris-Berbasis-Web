<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'manager']);
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id'   => 'sometimes|exists:categories,id',
            'sku'           => 'sometimes|string|max:50|unique:products,sku,' . $productId,
            'name'          => 'sometimes|string|max:200',
            'description'   => 'nullable|string',
            'standard_cost' => 'sometimes|numeric|min:0',
            'list_price'    => 'sometimes|numeric|min:0',
            'is_active'     => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique'         => 'SKU produk sudah digunakan.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
        ];
    }
}
