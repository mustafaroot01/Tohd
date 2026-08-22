<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GenerateActivationCodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxBatch = config('activation.max_batch_quantity', 500);

        return [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', "max:{$maxBatch}"],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
