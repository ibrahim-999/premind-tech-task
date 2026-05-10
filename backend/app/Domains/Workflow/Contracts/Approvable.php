<?php

namespace App\Domains\Workflow\Contracts;

use App\Domains\User\Models\User;

interface Approvable
{
    public function getKey();

    public function approvalAmount(): ?float;

    public function approvalAttributes(): array;

    public function approvalSubmitter(): User;
}
