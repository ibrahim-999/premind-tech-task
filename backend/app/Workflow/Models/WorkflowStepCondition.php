<?php

namespace App\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepCondition extends Model
{
    protected $fillable = ['workflow_step_id', 'type', 'config'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }
}
