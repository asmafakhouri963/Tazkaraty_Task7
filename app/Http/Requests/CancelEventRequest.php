<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event'); // الفعالية المراد إلغاؤها
        
        // التحقق أن المستخدم الحالي هو منظم هذه الفعالية حصراً (منع منظم من إلغاء فعالية غيره)
        return $event && $event->organizer_id === auth()->id();
    }

    public function forbiddenResponse()
    {
        // إرجاع رمز HTTP 403 مخصص عند فشل الصلاحية
        return response()->json([
            'message' => 'غير مصرح لك بإلغاء هذه الفعالية لأنها لا تتبع لك.'
        ], 403);
    }

    public function rules(): array
    {
        return [];
    }
}