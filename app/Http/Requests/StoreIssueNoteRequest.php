<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIssueNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('issue'));
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:10000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'body.required' => 'Vui lòng nhập nội dung ghi chú.',
            'body.string' => 'Nội dung ghi chú phải là văn bản.',
            'body.max' => 'Ghi chú không được vượt quá 10.000 ký tự.',
        ];
    }
}
