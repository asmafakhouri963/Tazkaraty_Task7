<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // السماح لأي مستخدم مسجل الدخول بإنشاء حجز
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'ticket_type_id' => ['required', 'exists:ticket_types,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:5'], // الحد المسموح للشراء
        ];
    }

    public function messages(): array
    {
        return [
            'ticket_type_id.required' => 'نوع التذكرة مطلوب.',
            'ticket_type_id.exists' => 'نوع التذكرة غير موجود.',
            'quantity.required' => 'عدد التذاكر مطلوب.',
            'quantity.min' => 'يجب أن تطلب تذكرة واحدة على الأقل.',
            'quantity.max' => 'عذراً، لقد تجاوزت الحد المسموح للشراء في عملية واحدة.',
        ];
    }
}