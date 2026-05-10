<?php

namespace App\Domains\Workflow\Conditions\Handlers;

use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Contracts\ConditionHandler;

class FieldIn implements ConditionHandler
{
    public function evaluate(Approvable $subject, array $config): bool
    {
        $attributes = $subject->approvalAttributes();
        $field = (string) $config['field'];
        $values = (array) $config['values'];

        if (! array_key_exists($field, $attributes)) {
            return false;
        }

        return in_array($attributes[$field], $values, false);
    }

    public function configSchema(): array
    {
        return [
            'field' => ['type' => 'string', 'required' => true],
            'values' => ['type' => 'array', 'required' => true],
        ];
    }
}
