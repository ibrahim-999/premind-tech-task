<?php

namespace App\Workflow\Conditions\Handlers;

use App\Workflow\Contracts\Approvable;
use App\Workflow\Contracts\ConditionHandler;

class AmountBetween implements ConditionHandler
{
    public function evaluate(Approvable $subject, array $config): bool
    {
        $amount = $subject->approvalAmount();

        if ($amount === null) {
            return false;
        }

        return $amount >= (float) $config['min'] && $amount <= (float) $config['max'];
    }

    public function configSchema(): array
    {
        return [
            'min' => ['type' => 'number', 'required' => true],
            'max' => ['type' => 'number', 'required' => true],
        ];
    }
}
