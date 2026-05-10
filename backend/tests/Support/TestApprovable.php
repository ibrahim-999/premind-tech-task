<?php

namespace Tests\Support;

use App\Models\User;
use App\Workflow\Contracts\Approvable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestApprovable extends Model implements Approvable
{
    protected $table = 'test_approvables';

    protected $fillable = [
        'name',
        'amount',
        'category',
        'department_id',
        'submitter_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    public function approvalAmount(): ?float
    {
        return $this->amount;
    }

    public function approvalAttributes(): array
    {
        return [
            'category' => $this->category,
            'department_id' => $this->department_id,
            'name' => $this->name,
        ];
    }

    public function approvalSubmitter(): User
    {
        return $this->submitter;
    }
}
