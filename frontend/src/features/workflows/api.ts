import { apiClient } from '@/shared/api/client'
import type { PaginatedResponse } from '@/features/purchase-orders/types'
import type { Workflow, WorkflowVersion } from './types'

export interface NewWorkflowInput {
  name: string
  subject_type: string
  is_active?: boolean
}

export interface NewVersionInput {
  steps: Array<{
    name: string
    approval_mode: string
    required_approvals: number
    conditions: Array<{ type: string; config: Record<string, unknown> }>
    approvers: Array<{ resolver_type: string; config: Record<string, unknown> }>
  }>
}

export async function listWorkflows(): Promise<PaginatedResponse<Workflow>> {
  const res = await apiClient.get<PaginatedResponse<Workflow>>('/workflows')
  return res.data
}

export async function getWorkflow(id: number | string): Promise<Workflow> {
  const res = await apiClient.get<{ data: Workflow }>(`/workflows/${id}`)
  return res.data.data
}

export async function getWorkflowVersion(id: number | string): Promise<WorkflowVersion> {
  const res = await apiClient.get<{ data: WorkflowVersion }>(`/workflow-versions/${id}`)
  return res.data.data
}

export async function createWorkflow(input: NewWorkflowInput): Promise<Workflow> {
  const res = await apiClient.post<{ data: Workflow }>('/workflows', input)
  return res.data.data
}

export async function createWorkflowVersion(
  workflowId: number | string,
  input: NewVersionInput,
): Promise<WorkflowVersion> {
  const res = await apiClient.post<{ data: WorkflowVersion }>(
    `/workflows/${workflowId}/versions`,
    input,
  )
  return res.data.data
}

export async function publishWorkflowVersion(
  versionId: number | string,
): Promise<WorkflowVersion> {
  const res = await apiClient.post<{ data: WorkflowVersion }>(
    `/workflow-versions/${versionId}/publish`,
    {},
  )
  return res.data.data
}
