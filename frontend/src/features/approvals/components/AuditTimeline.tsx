import { cn } from '@/shared/utils/cn'
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

function eventTone(eventType: string): string {
  if (eventType === 'process_approved') return 'text-green-700'
  if (eventType === 'process_rejected' || eventType === 'process_cancelled') return 'text-red-700'
  if (eventType === 'no_approvers_available') return 'text-amber-700'
  if (eventType === 'action_recorded') return 'text-blue-700'
  return 'text-gray-700'
}

function describePayload(eventType: string, payload: Record<string, unknown> | null): string {
  if (payload === null) return ''
  if (eventType === 'action_recorded') {
    const action = String(payload.action ?? '')
    const comment = payload.comment !== null && payload.comment !== '' ? ` — "${String(payload.comment)}"` : ''
    return `${action}${comment}`
  }
  if (eventType === 'step_entered' || eventType === 'step_completed' || eventType === 'step_skipped') {
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

function formatDate(iso: string | null): string {
  if (iso === null) return ''
  return new Date(iso).toLocaleString()
}

export function AuditTimeline({ entries }: { entries: AuditLogEntry[] }) {
  if (entries.length === 0) {
    return <p className="text-sm text-gray-500">No events yet.</p>
  }

  return (
    <ol className="space-y-2 border-l border-gray-200 pl-4">
      {entries.map((entry) => {
        const label = labelFor[entry.event_type] ?? entry.event_type
        const detail = describePayload(entry.event_type, entry.payload)
        return (
          <li key={entry.id} className="text-xs text-gray-700">
            <div className="flex items-baseline justify-between gap-2">
              <span className={cn('font-medium', eventTone(entry.event_type))}>{label}</span>
              <span className="text-gray-400">{formatDate(entry.occurred_at)}</span>
            </div>
            {detail !== '' ? <p className="text-gray-600">{detail}</p> : null}
            {entry.actor !== null && entry.actor !== undefined ? (
              <p className="text-gray-400">by {entry.actor.name}</p>
            ) : null}
          </li>
        )
      })}
    </ol>
  )
}
