<?php

namespace App\Domains\Workflow\Engine;

use App\Domains\User\Models\User;
use App\Domains\Workflow\Contracts\Approvable;
use App\Domains\Workflow\Enums\ActionType;
use App\Domains\Workflow\Enums\ApprovalMode;
use App\Domains\Workflow\Enums\ProcessStatus;
use App\Domains\Workflow\Enums\StepInstanceStatus;
use App\Domains\Workflow\Events\ActionRecorded;
use App\Domains\Workflow\Events\NoApproversAvailable;
use App\Domains\Workflow\Events\ProcessApproved;
use App\Domains\Workflow\Events\ProcessCancelled;
use App\Domains\Workflow\Events\ProcessRejected;
use App\Domains\Workflow\Events\ProcessStarted;
use App\Domains\Workflow\Events\StepCompleted;
use App\Domains\Workflow\Events\StepEntered;
use App\Domains\Workflow\Events\StepInjected;
use App\Domains\Workflow\Events\StepSkipped;
use App\Domains\Workflow\Exceptions\InvalidActionState;
use App\Domains\Workflow\Exceptions\NoActiveWorkflow;
use App\Domains\Workflow\Exceptions\NotAnAssignee;
use App\Domains\Workflow\Exceptions\ProcessAlreadyPending;
use App\Domains\Workflow\Models\ApprovalAction;
use App\Domains\Workflow\Models\ApprovalProcess;
use App\Domains\Workflow\Models\ApprovalStepAssignee;
use App\Domains\Workflow\Models\ApprovalStepInstance;
use App\Domains\Workflow\Models\Workflow;
use App\Domains\Workflow\Models\WorkflowStep;
use App\Domains\Workflow\Models\WorkflowVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkflowEngine
{
    public function __construct(private readonly StepEvaluator $evaluator) {}

    public function start(Approvable $subject): ApprovalProcess
    {
        return DB::transaction(function () use ($subject) {
            $subjectType = $this->morphClassFor($subject);
            $subjectId = $subject->getKey();

            $existing = ApprovalProcess::query()
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subjectId)
                ->where('status', ProcessStatus::Pending)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw new ProcessAlreadyPending($subjectType, $subjectId);
            }

            $version = $this->resolveActiveVersion($subjectType);

            $process = ApprovalProcess::create([
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'workflow_version_id' => $version->id,
                'status' => ProcessStatus::Pending,
                'started_at' => now(),
            ]);

            ProcessStarted::dispatch($process, $subject);

            $this->advanceProcess($process, $subject);

            return $process->refresh();
        });
    }

    public function submitAction(
        ApprovalStepInstance $stepInstance,
        User $user,
        ActionType $action,
        ?string $comment,
        string $idempotencyKey,
    ): ApprovalAction {
        return DB::transaction(function () use ($stepInstance, $user, $action, $comment, $idempotencyKey) {
            $existing = ApprovalAction::where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing;
            }

            $stepInstance = ApprovalStepInstance::query()
                ->whereKey($stepInstance->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($stepInstance->status !== StepInstanceStatus::Pending) {
                throw new InvalidActionState('Step is not pending; no action can be recorded.');
            }

            $process = ApprovalProcess::query()
                ->whereKey($stepInstance->approval_process_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($process->status !== ProcessStatus::Pending) {
                throw new InvalidActionState('Process is not pending; no action can be recorded.');
            }

            $isAssignee = $stepInstance->assignees()
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->getKey())
                      ->orWhere('delegated_to_user_id', $user->getKey());
                })
                ->exists();

            if (! $isAssignee) {
                throw new NotAnAssignee();
            }

            $alreadyActed = $stepInstance->actions()
                ->where('user_id', $user->getKey())
                ->exists();

            if ($alreadyActed) {
                throw new InvalidActionState('User has already acted on this step.');
            }

            $actionRow = ApprovalAction::create([
                'approval_step_instance_id' => $stepInstance->id,
                'user_id' => $user->getKey(),
                'action' => $action,
                'comment' => $comment,
                'idempotency_key' => $idempotencyKey,
                'created_at' => now(),
            ]);

            ActionRecorded::dispatch($actionRow);

            if ($action === ActionType::Reject) {
                $this->finalizeStep($stepInstance, StepInstanceStatus::Rejected);
                $this->finalizeProcess($process, ProcessStatus::Rejected, $comment ?? '');
                return $actionRow;
            }

            $this->advanceOnApprove($stepInstance, $process);

            return $actionRow;
        });
    }

    public function injectAdHocStep(
        ApprovalProcess $process,
        User $injectedBy,
        string $name,
        string $resolverType,
        array $resolverConfig,
        string $reason,
    ): ApprovalStepInstance {
        return DB::transaction(function () use ($process, $injectedBy, $name, $resolverType, $resolverConfig, $reason) {
            $process = ApprovalProcess::query()->whereKey($process->id)->lockForUpdate()->firstOrFail();

            if ($process->status !== ProcessStatus::Pending) {
                throw new InvalidActionState('Cannot inject step into a non-pending process.');
            }

            $stepInstance = ApprovalStepInstance::create([
                'approval_process_id' => $process->id,
                'workflow_step_id' => null,
                'ad_hoc_name' => $name,
                'ad_hoc_resolver_type' => $resolverType,
                'ad_hoc_resolver_config' => $resolverConfig,
                'added_by_user_id' => $injectedBy->getKey(),
                'ad_hoc_reason' => $reason,
                'status' => StepInstanceStatus::Pending,
                'started_at' => now(),
            ]);

            StepInjected::dispatch($stepInstance, $injectedBy, $reason);

            $subject = $this->loadSubject($process);
            $assignees = $this->evaluator->resolveAssigneesForAdHoc($stepInstance, $subject);

            $this->materializeAssignees($stepInstance, $assignees);

            if ($assignees->isEmpty()) {
                NoApproversAvailable::dispatch($stepInstance);
            }

            if ($process->current_step_instance_id === null) {
                $process->update(['current_step_instance_id' => $stepInstance->id]);
            }

            StepEntered::dispatch($stepInstance);

            return $stepInstance;
        });
    }

    public function cancel(ApprovalProcess $process, ?User $actor = null, ?string $reason = null): ApprovalProcess
    {
        return DB::transaction(function () use ($process, $actor, $reason) {
            $process = ApprovalProcess::query()->whereKey($process->id)->lockForUpdate()->firstOrFail();

            if ($process->status !== ProcessStatus::Pending) {
                throw new InvalidActionState('Only pending processes can be cancelled.');
            }

            $process->update([
                'status' => ProcessStatus::Cancelled,
                'completed_at' => now(),
                'lock_version' => $process->lock_version + 1,
            ]);

            ApprovalStepInstance::where('approval_process_id', $process->id)
                ->where('status', StepInstanceStatus::Pending->value)
                ->update([
                    'status' => StepInstanceStatus::Skipped->value,
                    'completed_at' => now(),
                ]);

            ProcessCancelled::dispatch($process, $this->loadSubject($process), $reason);

            return $process->refresh();
        });
    }

    private function advanceProcess(ApprovalProcess $process, Approvable $subject): void
    {
        $consumedStepIds = ApprovalStepInstance::query()
            ->where('approval_process_id', $process->id)
            ->whereNotNull('workflow_step_id')
            ->pluck('workflow_step_id')
            ->all();

        $candidateSteps = $process->version->steps()
            ->whereNotIn('id', $consumedStepIds)
            ->orderBy('order')
            ->get();

        foreach ($candidateSteps as $step) {
            if (! $this->evaluator->shouldApply($step, $subject)) {
                $this->createSkippedStepInstance($process, $step);
                StepSkipped::dispatch($process, $step);
                continue;
            }

            $stepInstance = $this->createStepInstance($process, $step);
            $assignees = $this->evaluator->resolveAssigneesForStep($step, $subject);
            $this->materializeAssignees($stepInstance, $assignees);

            $process->update(['current_step_instance_id' => $stepInstance->id]);
            StepEntered::dispatch($stepInstance);

            if ($assignees->isEmpty()) {
                NoApproversAvailable::dispatch($stepInstance);
            }

            return;
        }

        $pending = ApprovalStepInstance::query()
            ->where('approval_process_id', $process->id)
            ->where('status', StepInstanceStatus::Pending->value)
            ->orderBy('id')
            ->first();

        if ($pending !== null) {
            $process->update(['current_step_instance_id' => $pending->id]);
            return;
        }

        $this->finalizeProcess($process, ProcessStatus::Approved);
    }

    private function advanceOnApprove(ApprovalStepInstance $stepInstance, ApprovalProcess $process): void
    {
        $step = $stepInstance->step;
        $approvalMode = $stepInstance->isAdHoc() ? ApprovalMode::Single : $step->approval_mode;
        $required = $stepInstance->isAdHoc() ? 1 : (int) $step->required_approvals;

        $approvedCount = $stepInstance->actions()->where('action', ActionType::Approve->value)->count();
        $assigneesCount = $stepInstance->assignees()->count();

        $threshold = match ($approvalMode) {
            ApprovalMode::Single, ApprovalMode::ParallelAny => 1,
            ApprovalMode::ParallelAll => max(1, $assigneesCount),
            ApprovalMode::Quorum => max(1, $required),
        };

        if ($approvedCount < $threshold) {
            return;
        }

        $this->finalizeStep($stepInstance, StepInstanceStatus::Approved);

        $subject = $this->loadSubject($process);
        $this->advanceProcess($process, $subject);
    }

    private function createSkippedStepInstance(ApprovalProcess $process, WorkflowStep $step): ApprovalStepInstance
    {
        return ApprovalStepInstance::create([
            'approval_process_id' => $process->id,
            'workflow_step_id' => $step->id,
            'status' => StepInstanceStatus::Skipped,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    private function createStepInstance(ApprovalProcess $process, WorkflowStep $step): ApprovalStepInstance
    {
        return ApprovalStepInstance::create([
            'approval_process_id' => $process->id,
            'workflow_step_id' => $step->id,
            'status' => StepInstanceStatus::Pending,
            'started_at' => now(),
        ]);
    }

    private function materializeAssignees(ApprovalStepInstance $stepInstance, $assignees): void
    {
        foreach ($assignees as $row) {
            ApprovalStepAssignee::create([
                'approval_step_instance_id' => $stepInstance->id,
                'user_id' => $row['user']->getKey(),
                'resolver_source' => $row['resolver_source'],
                'created_at' => now(),
            ]);
        }
    }

    private function finalizeStep(ApprovalStepInstance $stepInstance, StepInstanceStatus $status): void
    {
        $stepInstance->update([
            'status' => $status,
            'completed_at' => now(),
        ]);

        StepCompleted::dispatch($stepInstance, $status);
    }

    private function finalizeProcess(ApprovalProcess $process, ProcessStatus $status, string $reason = ''): void
    {
        $process->update([
            'status' => $status,
            'completed_at' => now(),
            'current_step_instance_id' => null,
            'lock_version' => $process->lock_version + 1,
        ]);

        $subject = $this->loadSubject($process);

        if ($status === ProcessStatus::Approved) {
            ProcessApproved::dispatch($process, $subject);
        } elseif ($status === ProcessStatus::Rejected) {
            ProcessRejected::dispatch($process, $subject, $reason);
        }
    }

    private function morphClassFor(Approvable $subject): string
    {
        if (method_exists($subject, 'getMorphClass')) {
            return $subject->getMorphClass();
        }

        return $subject::class;
    }

    private function loadSubject(ApprovalProcess $process): Approvable
    {
        $subject = $process->subject;

        if (! $subject instanceof Approvable) {
            throw new InvalidActionState('Subject does not implement Approvable.');
        }

        return $subject;
    }

    private function resolveActiveVersion(string $subjectType): WorkflowVersion
    {
        $workflow = Workflow::query()
            ->where('subject_type', $subjectType)
            ->where('is_active', true)
            ->first();

        if ($workflow === null) {
            throw new NoActiveWorkflow($subjectType);
        }

        $version = $workflow->activePublishedVersion();

        if ($version === null) {
            throw new NoActiveWorkflow($subjectType);
        }

        return $version;
    }
}
