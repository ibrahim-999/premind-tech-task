<?php

namespace App\Domains\Workflow\Listeners;

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
use App\Domains\Workflow\Models\ApprovalAuditLog;
use Illuminate\Events\Dispatcher;

class WriteAuditLog
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            ProcessStarted::class => 'onProcessStarted',
            StepEntered::class => 'onStepEntered',
            StepSkipped::class => 'onStepSkipped',
            ActionRecorded::class => 'onActionRecorded',
            StepCompleted::class => 'onStepCompleted',
            ProcessApproved::class => 'onProcessApproved',
            ProcessRejected::class => 'onProcessRejected',
            ProcessCancelled::class => 'onProcessCancelled',
            NoApproversAvailable::class => 'onNoApproversAvailable',
            StepInjected::class => 'onStepInjected',
        ];
    }

    public function onProcessStarted(ProcessStarted $e): void
    {
        $this->write($e->process->id, 'process_started', [
            'workflow_version_id' => $e->process->workflow_version_id,
            'subject_type' => $e->process->subject_type,
            'subject_id' => $e->process->subject_id,
        ]);
    }

    public function onStepEntered(StepEntered $e): void
    {
        $this->write($e->stepInstance->approval_process_id, 'step_entered', [
            'step_instance_id' => $e->stepInstance->id,
            'step_name' => $e->stepInstance->displayName(),
            'is_ad_hoc' => $e->stepInstance->isAdHoc(),
            'assignee_count' => $e->stepInstance->assignees()->count(),
        ]);
    }

    public function onStepSkipped(StepSkipped $e): void
    {
        $this->write($e->process->id, 'step_skipped', [
            'workflow_step_id' => $e->step->id,
            'step_name' => $e->step->name,
        ]);
    }

    public function onActionRecorded(ActionRecorded $e): void
    {
        $this->write(
            (int) $e->action->stepInstance->approval_process_id,
            'action_recorded',
            [
                'step_instance_id' => $e->action->approval_step_instance_id,
                'action' => $e->action->action->value,
                'comment' => $e->action->comment,
            ],
            (int) $e->action->user_id,
        );
    }

    public function onStepCompleted(StepCompleted $e): void
    {
        $this->write($e->stepInstance->approval_process_id, 'step_completed', [
            'step_instance_id' => $e->stepInstance->id,
            'step_name' => $e->stepInstance->displayName(),
            'final_status' => $e->finalStatus->value,
        ]);
    }

    public function onProcessApproved(ProcessApproved $e): void
    {
        $this->write($e->process->id, 'process_approved', []);
    }

    public function onProcessRejected(ProcessRejected $e): void
    {
        $this->write($e->process->id, 'process_rejected', ['reason' => $e->reason]);
    }

    public function onProcessCancelled(ProcessCancelled $e): void
    {
        $this->write($e->process->id, 'process_cancelled', ['reason' => $e->reason]);
    }

    public function onNoApproversAvailable(NoApproversAvailable $e): void
    {
        $this->write($e->stepInstance->approval_process_id, 'no_approvers_available', [
            'step_instance_id' => $e->stepInstance->id,
            'step_name' => $e->stepInstance->displayName(),
        ]);
    }

    public function onStepInjected(StepInjected $e): void
    {
        $this->write(
            $e->stepInstance->approval_process_id,
            'step_injected',
            [
                'step_instance_id' => $e->stepInstance->id,
                'name' => $e->stepInstance->ad_hoc_name,
                'resolver_type' => $e->stepInstance->ad_hoc_resolver_type,
                'reason' => $e->reason,
            ],
            $e->injectedBy->getKey(),
        );
    }

    private function write(int $processId, string $type, array $payload, ?int $actorId = null): void
    {
        ApprovalAuditLog::create([
            'approval_process_id' => $processId,
            'event_type' => $type,
            'payload' => $payload,
            'actor_id' => $actorId,
            'occurred_at' => now(),
        ]);
    }
}
