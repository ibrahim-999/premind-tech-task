<?php

namespace App\Workflow\Contracts;

use App\Models\User;

interface Approvable
{
    public function getKey();

    public function approvalAmount(): ?float;

    public function approvalAttributes(): array;

    public function approvalSubmitter(): User;
}
