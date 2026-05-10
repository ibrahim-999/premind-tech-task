import { Check, Minus, X } from 'lucide-react'
import { Avatar } from '@/shared/components/Avatar'
import { Badge } from '@/shared/components/Badge'
import { cn } from '@/shared/utils/cn'
import { formatRelative } from '@/shared/utils/format'
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

function StepIcon({ status, isCurrent }: { status: StepInstanceStatus; isCurrent: boolean }) {
  const base = 'relative flex h-8 w-8 items-center justify-center rounded-full ring-4'

  if (status === 'approved') {
    return (
      <div className={cn(base, 'bg-emerald-500 text-white ring-emerald-100')}>
        <Check size={16} strokeWidth={3} />
      </div>
    )
  }
  if (status === 'rejected') {
    return (
      <div className={cn(base, 'bg-red-500 text-white ring-red-100')}>
        <X size={16} strokeWidth={3} />
      </div>
    )
  }
  if (status === 'skipped') {
    return (
      <div className={cn(base, 'bg-gray-200 text-gray-400 ring-gray-100')}>
        <Minus size={14} />
      </div>
    )
  }
  return (
    <div
      className={cn(
        base,
        isCurrent
          ? 'bg-amber-500 text-white ring-amber-100 shadow-sm'
          : 'bg-gray-300 text-white ring-gray-100',
      )}
    >
      <span className="block h-2 w-2 rounded-full bg-white" />
    </div>
  )
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
    <ol className="relative">
      {steps.map((step, idx) => {
        const isCurrent = step.id === currentStepInstanceId
        const isLast = idx === steps.length - 1
        return (
          <li key={step.id} className="relative flex gap-4 pb-6 last:pb-0">
            {!isLast ? (
              <span
                aria-hidden
                className="absolute left-4 top-8 h-full w-px -translate-x-1/2 bg-gray-200"
              />
            ) : null}
            <div className="relative">
              <StepIcon status={step.status} isCurrent={isCurrent} />
            </div>
            <div className="min-w-0 flex-1 pt-0.5">
              <div className="flex flex-wrap items-center gap-2">
                <h3
                  className={cn(
                    'text-sm font-semibold',
                    step.status === 'skipped' ? 'text-gray-400' : 'text-gray-900',
                  )}
                >
                  {step.name}
                </h3>
                <Badge tone={toneFor[step.status]}>{labelFor[step.status]}</Badge>
                {step.is_ad_hoc ? <Badge tone="blue">ad-hoc</Badge> : null}
                {isCurrent && step.status === 'pending' ? (
                  <Badge tone="amber">current</Badge>
                ) : null}
              </div>
              <p className="mt-0.5 text-xs text-gray-500">
                {step.completed_at !== null
                  ? `Finished ${formatRelative(step.completed_at)}`
                  : `Started ${formatRelative(step.started_at)}`}
              </p>
              {(step.assignees ?? []).length > 0 && step.status !== 'skipped' ? (
                <div className="mt-2 flex items-center gap-1.5">
                  <span className="text-xs text-gray-500">Assignees:</span>
                  <div className="flex -space-x-1.5">
                    {(step.assignees ?? []).map((a) => (
                      <Avatar
                        key={a.id}
                        name={a.user?.name ?? '?'}
                        size="sm"
                        className="ring-2 ring-white"
                      />
                    ))}
                  </div>
                </div>
              ) : null}
              {(step.actions ?? []).length > 0 ? (
                <ul className="mt-3 space-y-2">
                  {(step.actions ?? []).map((a) => (
                    <li
                      key={a.id}
                      className={cn(
                        'rounded-md border-l-2 bg-gray-50 px-3 py-2 text-xs',
                        a.action === 'approve'
                          ? 'border-emerald-400'
                          : 'border-red-400',
                      )}
                    >
                      <div className="flex items-center gap-2">
                        <span
                          className={cn(
                            'font-semibold',
                            a.action === 'approve' ? 'text-emerald-700' : 'text-red-700',
                          )}
                        >
                          {a.action === 'approve' ? 'Approved' : 'Rejected'}
                        </span>
                        <span className="text-gray-700">by {a.user?.name ?? 'unknown'}</span>
                        <span className="text-gray-400">· {formatRelative(a.created_at)}</span>
                      </div>
                      {a.comment !== null && a.comment !== '' ? (
                        <p className="mt-1 italic text-gray-600">"{a.comment}"</p>
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
