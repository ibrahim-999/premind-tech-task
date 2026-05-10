<?php

namespace Tests\Support;

use App\Domains\Workflow\Approvers\Resolvers\DepartmentHeadResolver;
use App\Domains\Workflow\Approvers\Resolvers\DirectManagerResolver;
use App\Domains\Workflow\Approvers\Resolvers\RoleResolver;
use App\Domains\Workflow\Approvers\Resolvers\SpecificUserResolver;
use App\Domains\Workflow\Enums\ApprovalMode;
use App\Domains\Workflow\Models\Workflow;
use App\Domains\Workflow\Models\WorkflowVersion;
use App\Domains\Workflow\Services\WorkflowDefinitionService;

class WorkflowFactory
{
    private const RESOLVER_MAP = [
        DirectManagerResolver::class => 'direct_manager',
        RoleResolver::class => 'role',
        SpecificUserResolver::class => 'specific_user',
        DepartmentHeadResolver::class => 'department_head',
    ];

    private string $subjectType;
    private string $name = 'Test Workflow';

    /** @var array<int, array<string, mixed>> */
    private array $steps = [];

    public static function for(string $subjectClass): self
    {
        $instance = new self();
        $instance->subjectType = (new $subjectClass)->getMorphClass();

        return $instance;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function step(string $name): self
    {
        $this->steps[] = [
            'name' => $name,
            'approval_mode' => ApprovalMode::Single->value,
            'required_approvals' => 1,
            'conditions' => [],
            'approvers' => [],
        ];

        return $this;
    }

    public function approvedBy(string $resolverClassOrType, array $config = []): self
    {
        $type = self::RESOLVER_MAP[$resolverClassOrType] ?? $resolverClassOrType;
        $this->steps[$this->currentIndex()]['approvers'][] = [
            'resolver_type' => $type,
            'config' => $config,
        ];

        return $this;
    }

    public function whenAmountGte(float $amount): self
    {
        return $this->withCondition('amount_gte', ['amount' => $amount]);
    }

    public function whenAmountLte(float $amount): self
    {
        return $this->withCondition('amount_lte', ['amount' => $amount]);
    }

    public function whenAmountBetween(float $min, float $max): self
    {
        return $this->withCondition('amount_between', ['min' => $min, 'max' => $max]);
    }

    public function whenFieldEq(string $field, mixed $value): self
    {
        return $this->withCondition('field_eq', ['field' => $field, 'value' => $value]);
    }

    public function whenFieldIn(string $field, array $values): self
    {
        return $this->withCondition('field_in', ['field' => $field, 'values' => $values]);
    }

    public function inParallel(): self
    {
        $this->steps[$this->currentIndex()]['approval_mode'] = ApprovalMode::ParallelAny->value;

        return $this;
    }

    public function requireAll(): self
    {
        $this->steps[$this->currentIndex()]['approval_mode'] = ApprovalMode::ParallelAll->value;

        return $this;
    }

    public function quorum(int $required): self
    {
        $this->steps[$this->currentIndex()]['approval_mode'] = ApprovalMode::Quorum->value;
        $this->steps[$this->currentIndex()]['required_approvals'] = $required;

        return $this;
    }

    public function publish(): WorkflowVersion
    {
        $workflow = Workflow::firstOrCreate(
            ['subject_type' => $this->subjectType],
            ['name' => $this->name, 'is_active' => true],
        );

        $service = app(WorkflowDefinitionService::class);
        $version = $service->createVersion($workflow, $this->steps);

        return $service->publish($version, null);
    }

    private function withCondition(string $type, array $config): self
    {
        $this->steps[$this->currentIndex()]['conditions'][] = [
            'type' => $type,
            'config' => $config,
        ];

        return $this;
    }

    private function currentIndex(): int
    {
        if ($this->steps === []) {
            throw new \LogicException('Call step() before configuring step properties.');
        }

        return count($this->steps) - 1;
    }
}
