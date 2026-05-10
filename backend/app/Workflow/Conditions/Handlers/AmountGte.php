<?php

namespace App\Workflow\Conditions\Handlers;

use App\Workflow\Contracts\Approvable;
use App\Workflow\Contracts\ConditionHandler;

class AmountGte implements ConditionHandler
{
    public function evaluate(Approvable $subject, array $config): bool
    {
        $amount = $subject->approvalAmount();

        if ($amount === null) {
            return false;
        }

        return $amount >= (float) $config['amount'];
    }

    public function configSchema(): array
    {
        return [
            'amount' => ['type' => 'number', 'required' => true],
        ];
    }
}
