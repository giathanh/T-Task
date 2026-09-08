<?php

namespace App\Http\Requests;

use App\Enums\IssuePriority;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('issue'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $projectId = $this->route('project')->id;
        $issueId = $this->route('issue')->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(IssueType::class)],
            'status' => ['required', Rule::enum(IssueStatus::class)],
            'priority' => ['required', Rule::enum(IssuePriority::class)],
            'severity' => ['nullable', 'required_if:type,'.IssueType::Bug->value, Rule::enum(IssueSeverity::class)],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'percent_done' => ['nullable', 'integer', 'between:0,100'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_private' => ['nullable', 'boolean'],
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('project_user', 'user_id')->where('project_id', $projectId),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::notIn([$issueId]),
                Rule::exists('issues', 'id')->where('project_id', $projectId),
            ],
            'watchers' => ['nullable', 'array'],
            'watchers.*' => [
                'integer',
                Rule::exists('project_user', 'user_id')->where('project_id', $projectId),
            ],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,txt',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.not_in' => 'Một issue không thể là task cha của chính nó.',
        ];
    }
}
