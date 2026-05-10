<?php

namespace App\Domains\Workflow\Models;

use App\Domains\Workflow\Enums\ApprovalMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    protected $fillable = [
        'workflow_version_id',
        'order',
        'name',
        'approval_mode',
        'required_approvals',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'required_approvals' => 'integer',
            'approval_mode' => ApprovalMode::class,
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(WorkflowStepCondition::class);
    }

    public function approvers(): HasMany
    {
        return $this->hasMany(WorkflowStepApprover::class);
    }
}
