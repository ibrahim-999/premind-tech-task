<?php

namespace App\Domains\Workflow\Models;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Enums\ActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'approval_step_instance_id',
        'user_id',
        'action',
        'comment',
        'idempotency_key',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => ActionType::class,
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
}
