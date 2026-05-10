<?php

namespace Tests\Support;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Contracts\Approvable;
use LogicException;

class InMemoryApprovable implements Approvable
{
    public function __construct(
        private readonly ?float $amount = null,
        private readonly array $attributes = [],
        private readonly int $key = 1,
        private readonly ?User $submitter = null,
    ) {}

    public function getKey()
    {
        return $this->key;
    }

    public function approvalAmount(): ?float
    {
        return $this->amount;
    }

    public function approvalAttributes(): array
    {
        return $this->attributes;
    }

    public function approvalSubmitter(): User
    {
        return $this->submitter ?? throw new LogicException('submitter not set on stub');
    }
}
