<?php

namespace App\Domains\Workflow\Conditions\Handlers;

use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Contracts\ConditionHandler;

class FieldEq implements ConditionHandler
{
    public function evaluate(Approvable $subject, array $config): bool
    {
        $attributes = $subject->approvalAttributes();
        $field = (string) $config['field'];

        if (! array_key_exists($field, $attributes)) {
            return false;
        }

        return $attributes[$field] == $config['value'];
    }

    public function configSchema(): array
    {
        return [
            'field' => ['type' => 'string', 'required' => true],
            'value' => ['type' => 'mixed', 'required' => true],
        ];
    }
}
