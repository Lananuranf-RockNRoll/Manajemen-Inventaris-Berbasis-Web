<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Otorisasi diserahkan sepenuhnya ke middleware role:admin,manager,staff di routes/api.php.
     * Method ini mengembalikan true agar tidak terjadi pengecekan dobel.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'        => 'required|exists:customers,id',
            'employee_id'        => 'nullable|exists:employees,id',
            'warehouse_id'       => 'required|exists:warehouses,id',
            'order_date'         => 'nullable|date',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'            => 'Minimal satu item produk harus ada.',
            'items.*.product_id.exists' => 'Produk tidak ditemukan.',
            'items.*.quantity.min'      => 'Jumlah item minimal 1.',
            'items.*.unit_price.min'    => 'Harga per unit tidak boleh negatif.',
        ];
    }
}
