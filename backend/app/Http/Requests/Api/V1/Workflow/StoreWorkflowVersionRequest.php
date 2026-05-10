<?php

namespace App\Http\Requests\Api\V1\Workflow;

use App\Domains\Workflow\Enums\ApprovalMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkflowVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        $modes = array_column(ApprovalMode::cases(), 'value');

        return [
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.name' => ['required', 'string', 'max:120'],
            'steps.*.approval_mode' => ['sometimes', Rule::in($modes)],
            'steps.*.required_approvals' => ['sometimes', 'integer', 'min:1'],
            'steps.*.conditions' => ['sometimes', 'array'],
            'steps.*.conditions.*.type' => ['required_with:steps.*.conditions', 'string', 'max:64'],
            'steps.*.conditions.*.config' => ['sometimes', 'array'],
            'steps.*.approvers' => ['required', 'array', 'min:1'],
            'steps.*.approvers.*.resolver_type' => ['required', 'string', 'max:64'],
            'steps.*.approvers.*.config' => ['sometimes', 'array'],
        ];
    }
}
