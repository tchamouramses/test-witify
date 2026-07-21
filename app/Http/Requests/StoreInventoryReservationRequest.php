<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryReservationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /**
     * Get the validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'warehouse_id.required' => __('Select a warehouse.'),
            'warehouse_id.integer' => __('The selected warehouse is invalid.'),
            'warehouse_id.exists' => __('The selected warehouse is unavailable.'),
            'quantity.required' => __('Enter a quantity to reserve.'),
            'quantity.integer' => __('The quantity must be a whole number.'),
            'quantity.min' => __('The quantity must be greater than zero.'),
            'quantity.max' => __('The quantity may not exceed 100,000 units.'),
        ];
    }
}
