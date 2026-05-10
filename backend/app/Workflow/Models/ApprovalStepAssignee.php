<?php

namespace App\Workflow\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStepAssignee extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'approval_step_instance_id',
        'user_id',
        'resolver_source',
        'delegated_to_user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function stepInstance(): BelongsTo
    {
        return $this->belongsTo(ApprovalStepInstance::class, 'approval_step_instance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delegatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to_user_id');
    }

    public function effectiveUserId(): int
    {
        return $this->delegated_to_user_id ?? $this->user_id;
    }
}
