<?php

namespace Tests\Unit\Workflow;

use App\Domains\Workflow\Approvers\ApproverResolverRegistry;
use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Contracts\ApproverResolver;
use App\Domains\Workflow\Exceptions\UnknownResolverType;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryApprovable;

class ApproverResolverRegistryTest extends TestCase
{
    public function test_register_and_resolve_dispatches_to_resolver(): void
    {
        $registry = new ApproverResolverRegistry();
        $resolver = $this->recordingResolver(collect(['user-a']));
        $registry->register('role', $resolver);
        $subject = new InMemoryApprovable();

        $result = $registry->resolve('role', ['role' => 'cfo'], $subject);

        $this->assertSame(['user-a'], $result->all());
        $this->assertSame(['role' => 'cfo'], $resolver->lastConfig);
        $this->assertSame($subject, $resolver->lastSubject);
    }

    public function test_has_returns_true_for_registered_type_and_false_otherwise(): void
    {
        $registry = new ApproverResolverRegistry();
        $registry->register('role', $this->recordingResolver(collect()));

        $this->assertTrue($registry->has('role'));
        $this->assertFalse($registry->has('manager'));
    }

    public function test_unknown_type_throws(): void
    {
        $registry = new ApproverResolverRegistry();

        $this->expectException(UnknownResolverType::class);

        $registry->resolve('does_not_exist', [], new InMemoryApprovable());
    }

    public function test_resolver_for_returns_registered_instance(): void
    {
        $registry = new ApproverResolverRegistry();
        $resolver = $this->recordingResolver(collect());
        $registry->register('role', $resolver);

        $this->assertSame($resolver, $registry->resolverFor('role'));
    }

    public function test_register_is_chainable_and_overwrites_previous_resolver(): void
    {
        $registry = new ApproverResolverRegistry();
        $first = $this->recordingResolver(collect(['old']));
        $second = $this->recordingResolver(collect(['new']));

        $registry->register('role', $first)->register('role', $second);

        $this->assertSame(['new'], $registry->resolve('role', [], new InMemoryApprovable())->all());
    }

    private function recordingResolver(Collection $users): ApproverResolver
    {
        return new class($users) implements ApproverResolver {
            public ?array $lastConfig = null;
            public ?Approvable $lastSubject = null;

            public function __construct(private readonly Collection $users) {}

            public function resolve(Approvable $subject, array $config): Collection
            {
                $this->lastSubject = $subject;
                $this->lastConfig = $config;

                return $this->users;
            }

            public function configSchema(): array
            {
                return [];
            }
        };
    }
}
