import { Badge } from '@/shared/components/Badge'
import type { WorkflowStepApprover } from '../types'

function describe(a: WorkflowStepApprover): string {
  switch (a.resolver_type) {
    case 'direct_manager':
      return 'direct manager'
    case 'department_head':
      return 'department head'
    case 'role':
      return `role: ${String(a.config.role ?? '?')}`
    case 'specific_user':
      return `user #${String(a.config.user_id ?? '?')}`
    default:
      return a.resolver_type
  }
}

export function ApproverPill({ approver }: { approver: WorkflowStepApprover }) {
  return <Badge tone="blue">{describe(approver)}</Badge>
}
