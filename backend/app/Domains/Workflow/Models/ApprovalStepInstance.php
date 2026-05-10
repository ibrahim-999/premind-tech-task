<?php

namespace App\Domains\Workflow\Models;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Enums\StepInstanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalStepInstance extends Model
{
    protected $fillable = [
        'approval_process_id',
        'workflow_step_id',
        'ad_hoc_name',
        'ad_hoc_resolver_type',
        'ad_hoc_resolver_config',
        'added_by_user_id',
        'ad_hoc_reason',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StepInstanceStatus::class,
            'ad_hoc_resolver_config' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ApprovalProcess::class, 'approval_process_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(ApprovalStepAssignee::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class);
    }

    public function isAdHoc(): bool
    {
        return $this->workflow_step_id === null;
    }

    public function displayName(): string
    {
        return $this->isAdHoc() ? (string) $this->ad_hoc_name : (string) $this->step?->name;
    }
}
