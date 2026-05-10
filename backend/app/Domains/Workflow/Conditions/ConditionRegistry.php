<?php

namespace App\Domains\Workflow\Conditions;

use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Contracts\ConditionHandler;
use App\Domains\Workflow\Exceptions\UnknownConditionType;

class ConditionRegistry
{
    /** @var array<string, ConditionHandler> */
    private array $handlers = [];

    public function register(string $type, ConditionHandler $handler): self
    {
        $this->handlers[$type] = $handler;

        return $this;
    }

    public function evaluate(string $type, array $config, Approvable $subject): bool
    {
        return $this->handlerFor($type)->evaluate($subject, $config);
    }

    public function has(string $type): bool
    {
        return isset($this->handlers[$type]);
    }

    public function handlerFor(string $type): ConditionHandler
    {
        if (! $this->has($type)) {
            throw new UnknownConditionType($type);
        }

        return $this->handlers[$type];
    }

    /** @return array<string, ConditionHandler> */
    public function all(): array
    {
        return $this->handlers;
    }
}
