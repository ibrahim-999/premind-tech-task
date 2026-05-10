<?php

namespace App\Http\Requests\Api\V1\PurchaseOrder;

use App\Domains\PurchaseOrder\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $po = $this->route('purchase_order');

        if (! $po instanceof PurchaseOrder) {
            return false;
        }

        return $this->user()?->can('update', $po) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'min:3', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'string', 'max:64'],
            'department_id' => ['sometimes', 'nullable', 'integer'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.name' => ['required_with:items', 'string', 'max:200'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }
}
