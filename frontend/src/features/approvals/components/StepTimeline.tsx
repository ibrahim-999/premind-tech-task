import { Check, Circle, X } from 'lucide-react'
import { Badge } from '@/shared/components/Badge'
import { cn } from '@/shared/utils/cn'
import type { ApprovalStepInstance, StepInstanceStatus } from '../types'

const labelFor: Record<StepInstanceStatus, string> = {
  pending: 'Pending',
  approved: 'Approved',
  rejected: 'Rejected',
  skipped: 'Skipped',
}

const toneFor: Record<StepInstanceStatus, 'amber' | 'green' | 'red' | 'gray'> = {
  pending: 'amber',
  approved: 'green',
  rejected: 'red',
  skipped: 'gray',
}

function StepIcon({ status }: { status: StepInstanceStatus }) {
  const base = 'flex h-8 w-8 items-center justify-center rounded-full border-2'
  if (status === 'approved') {
    return (
      <div className={cn(base, 'border-green-500 bg-green-50 text-green-700')}>
        <Check size={16} />
      </div>
    )
  }
  if (status === 'rejected') {
    return (
      <div className={cn(base, 'border-red-500 bg-red-50 text-red-700')}>
        <X size={16} />
      </div>
    )
  }
  if (status === 'skipped') {
    return (
      <div className={cn(base, 'border-gray-300 bg-gray-50 text-gray-400')}>
        <Circle size={12} />
      </div>
    )
  }
  return (
    <div className={cn(base, 'border-amber-400 bg-amber-50 text-amber-700')}>
      <Circle size={12} className="fill-current" />
    </div>
  )
}

function formatDate(iso: string | null): string {
  if (iso === null) return ''
  return new Date(iso).toLocaleString()
}

export function StepTimeline({
  steps,
  currentStepInstanceId,
}: {
  steps: ApprovalStepInstance[]
  currentStepInstanceId: number | null
}) {
  if (steps.length === 0) {
    return <p className="text-sm text-gray-500">No steps yet.</p>
  }

  return (
    <ol className="relative space-y-4">
      {steps.map((step, idx) => {
        const isCurrent = step.id === currentStepInstanceId
        const isLast = idx === steps.length - 1
        return (
          <li key={step.id} className="relative flex gap-3">
            {!isLast ? (
              <span
                aria-hidden
                className="absolute left-4 top-8 -translate-x-1/2 h-full w-0.5 bg-gray-200"
              />
            ) : null}
            <StepIcon status={step.status} />
            <div className="min-w-0 flex-1">
              <div className="flex items-center gap-2">
                <h3 className={cn('text-sm font-medium', isCurrent ? 'text-gray-900' : 'text-gray-700')}>
                  {step.name}
                </h3>
                <Badge tone={toneFor[step.status]}>{labelFor[step.status]}</Badge>
                {step.is_ad_hoc ? <Badge tone="blue">ad-hoc</Badge> : null}
                {isCurrent && step.status === 'pending' ? (
                  <Badge tone="amber">current</Badge>
                ) : null}
              </div>
              <p className="text-xs text-gray-500">
                Started {formatDate(step.started_at)}
                {step.completed_at !== null ? ` · finished ${formatDate(step.completed_at)}` : ''}
              </p>
              {(step.assignees ?? []).length > 0 ? (
                <p className="mt-1 text-xs text-gray-600">
                  Assignees:{' '}
                  {(step.assignees ?? [])
                    .map((a) => a.user?.name ?? `user #${String(a.user?.id ?? 'unknown')}`)
                    .join(', ')}
                </p>
              ) : null}
              {(step.actions ?? []).length > 0 ? (
                <ul className="mt-2 space-y-1">
                  {(step.actions ?? []).map((a) => (
                    <li key={a.id} className="text-xs text-gray-700">
                      <span
                        className={cn(
                          'font-medium',
                          a.action === 'approve' ? 'text-green-700' : 'text-red-700',
                        )}
                      >
                        {a.action === 'approve' ? 'Approved' : 'Rejected'}
                      </span>{' '}
                      by {a.user?.name ?? 'unknown'} · {formatDate(a.created_at)}
                      {a.comment !== null && a.comment !== '' ? (
                        <span className="block pl-4 text-gray-500">"{a.comment}"</span>
                      ) : null}
                    </li>
                  ))}
                </ul>
              ) : null}
            </div>
          </li>
        )
      })}
    </ol>
  )
}
