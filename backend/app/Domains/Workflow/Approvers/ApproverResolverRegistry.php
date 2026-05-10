<?php

namespace App\Domains\Workflow\Approvers;

use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Contracts\ApproverResolver;
use App\Domains\Workflow\Exceptions\UnknownResolverType;
use Illuminate\Support\Collection;

class ApproverResolverRegistry
{
    /** @var array<string, ApproverResolver> */
    private array $resolvers = [];

    public function register(string $type, ApproverResolver $resolver): self
    {
        $this->resolvers[$type] = $resolver;

        return $this;
    }

    public function resolve(string $type, array $config, Approvable $subject): Collection
    {
        return $this->resolverFor($type)->resolve($subject, $config);
    }

    public function has(string $type): bool
    {
        return isset($this->resolvers[$type]);
    }

    public function resolverFor(string $type): ApproverResolver
    {
        if (! $this->has($type)) {
            throw new UnknownResolverType($type);
        }

        return $this->resolvers[$type];
    }

    /** @return array<string, ApproverResolver> */
    public function all(): array
    {
        return $this->resolvers;
    }
}
