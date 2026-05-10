import { cn } from '@/shared/utils/cn'
import { formatDate } from '@/shared/utils/format'
import type { AuditLogEntry } from '../types'

const labelFor: Record<string, string> = {
  process_started: 'Process started',
  step_entered: 'Step entered',
  step_skipped: 'Step skipped',
  action_recorded: 'Action recorded',
  step_completed: 'Step completed',
  process_approved: 'Process approved',
  process_rejected: 'Process rejected',
  process_cancelled: 'Process cancelled',
  no_approvers_available: 'No approvers available',
  step_injected: 'Ad-hoc step injected',
}

function dotClass(eventType: string): string {
  if (eventType === 'process_approved' || eventType === 'step_completed') {
    return 'bg-emerald-500'
  }
  if (eventType === 'process_rejected' || eventType === 'process_cancelled') {
    return 'bg-red-500'
  }
  if (eventType === 'no_approvers_available') return 'bg-amber-500'
  if (eventType === 'action_recorded') return 'bg-brand-500'
  if (eventType === 'step_injected') return 'bg-violet-500'
  if (eventType === 'step_skipped') return 'bg-gray-300'
  return 'bg-gray-400'
}

function describePayload(eventType: string, payload: Record<string, unknown> | null): string {
  if (payload === null) return ''
  if (eventType === 'action_recorded') {
    const action = String(payload.action ?? '')
    const comment =
      payload.comment !== null && payload.comment !== ''
        ? ` — "${String(payload.comment)}"`
        : ''
    return `${action}${comment}`
  }
  if (
    eventType === 'step_entered' ||
    eventType === 'step_completed' ||
    eventType === 'step_skipped'
  ) {
    return String(payload.step_name ?? '')
  }
  if (eventType === 'process_rejected' || eventType === 'process_cancelled') {
    return String(payload.reason ?? '')
  }
  if (eventType === 'step_injected') {
    return `${String(payload.name ?? '')} (${String(payload.resolver_type ?? '')})`
  }
  return ''
}

export function AuditTimeline({ entries }: { entries: AuditLogEntry[] }) {
  if (entries.length === 0) {
    return <p className="text-sm text-gray-500">No events yet.</p>
  }

  return (
    <ol className="relative">
      {entries.map((entry, idx) => {
        const label = labelFor[entry.event_type] ?? entry.event_type
        const detail = describePayload(entry.event_type, entry.payload)
        const isLast = idx === entries.length - 1
        return (
          <li key={entry.id} className="relative flex gap-3 pb-4 last:pb-0">
            {!isLast ? (
              <span
                aria-hidden
                className="absolute left-1.5 top-3 h-full w-px bg-gray-200"
              />
            ) : null}
            <span
              className={cn(
                'relative mt-1.5 h-3 w-3 shrink-0 rounded-full ring-4 ring-white',
                dotClass(entry.event_type),
              )}
              aria-hidden
            />
            <div className="min-w-0 flex-1">
              <div className="flex items-baseline justify-between gap-2">
                <span className="text-xs font-medium text-gray-900">{label}</span>
                <span className="text-[11px] text-gray-400">
                  {formatDate(entry.occurred_at)}
                </span>
              </div>
              {detail !== '' ? <p className="text-xs text-gray-600">{detail}</p> : null}
              {entry.actor !== null && entry.actor !== undefined ? (
                <p className="text-[11px] text-gray-400">by {entry.actor.name}</p>
              ) : null}
            </div>
          </li>
        )
      })}
    </ol>
  )
}
