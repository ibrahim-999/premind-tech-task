import type { User } from '@/features/auth/types'

export type ProcessStatus = 'pending' | 'approved' | 'rejected' | 'cancelled'
export type StepInstanceStatus = 'pending' | 'approved' | 'rejected' | 'skipped'
export type ActionType = 'approve' | 'reject'

export interface ApprovalAction {
  id: number
  action: ActionType
  comment: string | null
  created_at: string | null
  user?: User
}

export interface ApprovalStepAssignee {
  id: number
  resolver_source: string
  user?: User
  delegated_to?: User | null
}

export interface ApprovalStepInstance {
  id: number
  name: string
  status: StepInstanceStatus
  is_ad_hoc: boolean
  started_at: string | null
  completed_at: string | null
  ad_hoc_reason: string | null
  assignees?: ApprovalStepAssignee[]
  actions?: ApprovalAction[]
}

export interface AuditLogEntry {
  id: number
  event_type: string
  payload: Record<string, unknown> | null
  occurred_at: string | null
  actor?: User | null
}

export interface ApprovalProcess {
  id: number
  subject_type: string
  subject_id: number
  status: ProcessStatus
  started_at: string | null
  completed_at: string | null
  current_step_instance_id: number | null
  current_step_instance?: ApprovalStepInstance | null
  step_instances?: ApprovalStepInstance[]
  audit_log?: AuditLogEntry[]
}

export interface InboxItem {
  step_instance_id: number
  step_name: string
  process_id: number
  subject_type: string
  subject: {
    id?: number
    title?: string
    amount?: number
    category?: string
    status?: string
  } | null
  submitted_by: User | null
  submitted_at: string | null
  started_at: string | null
}

export interface InboxResponse {
  data: InboxItem[]
  meta: {
    next_cursor: string | null
    prev_cursor: string | null
    per_page: number
  }
}

export function userIsAssignee(
  step: ApprovalStepInstance | null | undefined,
  userId: number,
): boolean {
  if (step === null || step === undefined) return false
  return (step.assignees ?? []).some(
    (a) => a.user?.id === userId || a.delegated_to?.id === userId,
  )
}

export function userHasActed(
  step: ApprovalStepInstance | null | undefined,
  userId: number,
): boolean {
  if (step === null || step === undefined) return false
  return (step.actions ?? []).some((a) => a.user?.id === userId)
}
