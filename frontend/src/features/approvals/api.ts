import { apiClient } from '@/shared/api/client'
import type { ApprovalProcess, InboxResponse } from './types'

export async function getInbox(cursor?: string | null): Promise<InboxResponse> {
  const res = await apiClient.get<InboxResponse>('/approvals/inbox', {
    params: cursor !== undefined && cursor !== null ? { cursor } : {},
  })
  return res.data
}

export async function getProcess(id: number | string): Promise<ApprovalProcess> {
  const res = await apiClient.get<{ data: ApprovalProcess }>(`/approvals/processes/${id}`)
  return res.data.data
}

export async function approveStep(
  stepInstanceId: number | string,
  comment: string | null,
  idempotencyKey: string,
): Promise<ApprovalProcess> {
  const res = await apiClient.post<{ data: ApprovalProcess }>(
    `/approvals/step-instances/${stepInstanceId}/approve`,
    comment !== null && comment !== '' ? { comment } : {},
    { headers: { 'Idempotency-Key': idempotencyKey } },
  )
  return res.data.data
}

export async function rejectStep(
  stepInstanceId: number | string,
  reason: string,
  idempotencyKey: string,
): Promise<ApprovalProcess> {
  const res = await apiClient.post<{ data: ApprovalProcess }>(
    `/approvals/step-instances/${stepInstanceId}/reject`,
    { reason },
    { headers: { 'Idempotency-Key': idempotencyKey } },
  )
  return res.data.data
}
