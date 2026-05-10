<?php

namespace App\Workflow\Models;

use App\Workflow\Enums\ProcessStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalProcess extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'workflow_version_id',
        'status',
        'current_step_instance_id',
        'started_at',
        'completed_at',
        'lock_version',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProcessStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    public function stepInstances(): HasMany
    {
        return $this->hasMany(ApprovalStepInstance::class);
    }

    public function currentStepInstance(): BelongsTo
    {
        return $this->belongsTo(ApprovalStepInstance::class, 'current_step_instance_id');
    }

    public function auditLog(): HasMany
    {
        return $this->hasMany(ApprovalAuditLog::class)->orderBy('occurred_at');
    }
}
