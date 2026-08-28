<?php

namespace App\Http\Requests\Api\V1\App;

use App\Enums\DeliveryType;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProfileDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // a hidden governorate is not offered, so it cannot be chosen
            'governorate_id' => ['required', 'uuid', Rule::exists('governorates', 'id')->where('is_active', true)],
            'gender' => ['required', Rule::enum(Gender::class)],
            'age' => ['required', 'integer', 'min:1', 'max:18'],
            'family_order' => ['required', 'integer', 'min:1', 'max:20'],
            'delivery_type' => ['required', Rule::enum(DeliveryType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'governorate_id.required' => 'المحافظة مطلوبة',
            'governorate_id.exists' => 'المحافظة المختارة غير متاحة',
            'gender.required' => 'الجنس مطلوب',
            'age.required' => 'العمر مطلوب',
            'age.min' => 'العمر يجب أن يكون سنة واحدة على الأقل',
            'age.max' => 'العمر يجب ألّا يتجاوز 18 سنة',
            'family_order.required' => 'التسلسل في العائلة مطلوب',
            'family_order.min' => 'التسلسل يبدأ من 1',
            'delivery_type.required' => 'نوع الولادة مطلوب',
        ];
    }
}
