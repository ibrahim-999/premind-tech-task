export type ApprovalMode = 'single' | 'parallel_any' | 'parallel_all' | 'quorum'

export interface WorkflowStepCondition {
  id: number
  type: string
  config: Record<string, unknown>
}

export interface WorkflowStepApprover {
  id: number
  resolver_type: string
  config: Record<string, unknown>
}

export interface WorkflowStep {
  id: number
  order: number
  name: string
  approval_mode: ApprovalMode
  required_approvals: number
  conditions?: WorkflowStepCondition[]
  approvers?: WorkflowStepApprover[]
}

export interface WorkflowVersion {
  id: number
  workflow_id: number
  version_number: number
  is_published: boolean
  published_at: string | null
  steps?: WorkflowStep[]
}

export interface Workflow {
  id: number
  name: string
  subject_type: string
  is_active: boolean
  created_at: string | null
  updated_at: string | null
  versions?: WorkflowVersion[]
}
