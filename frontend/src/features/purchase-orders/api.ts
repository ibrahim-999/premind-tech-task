import { apiClient } from '@/shared/api/client'
import type { PaginatedResponse, PurchaseOrder } from './types'

export interface PurchaseOrderInput {
  title: string
  description?: string | null
  category: string
  department_id?: number | null
  items: Array<{ name: string; quantity: number; unit_price: number }>
}

export async function listPurchaseOrders(): Promise<PaginatedResponse<PurchaseOrder>> {
  const res = await apiClient.get<PaginatedResponse<PurchaseOrder>>('/purchase-orders')
  return res.data
}

export async function getPurchaseOrder(id: number | string): Promise<PurchaseOrder> {
  const res = await apiClient.get<{ data: PurchaseOrder }>(`/purchase-orders/${id}`)
  return res.data.data
}

export async function createPurchaseOrder(input: PurchaseOrderInput): Promise<PurchaseOrder> {
  const res = await apiClient.post<{ data: PurchaseOrder }>('/purchase-orders', input)
  return res.data.data
}

export async function updatePurchaseOrder(
  id: number | string,
  input: Partial<PurchaseOrderInput>,
): Promise<PurchaseOrder> {
  const res = await apiClient.patch<{ data: PurchaseOrder }>(`/purchase-orders/${id}`, input)
  return res.data.data
}

export async function submitPurchaseOrder(
  id: number | string,
  idempotencyKey: string,
): Promise<PurchaseOrder> {
  const res = await apiClient.post<{ data: PurchaseOrder }>(
    `/purchase-orders/${id}/submit`,
    {},
    { headers: { 'Idempotency-Key': idempotencyKey } },
  )
  return res.data.data
}

export async function resubmitPurchaseOrder(
  id: number | string,
  idempotencyKey: string,
): Promise<PurchaseOrder> {
  const res = await apiClient.post<{ data: PurchaseOrder }>(
    `/purchase-orders/${id}/resubmit`,
    {},
    { headers: { 'Idempotency-Key': idempotencyKey } },
  )
  return res.data.data
}

export async function cancelPurchaseOrder(
  id: number | string,
  idempotencyKey: string,
  reason?: string,
): Promise<PurchaseOrder> {
  const res = await apiClient.post<{ data: PurchaseOrder }>(
    `/purchase-orders/${id}/cancel`,
    reason !== undefined ? { reason } : {},
    { headers: { 'Idempotency-Key': idempotencyKey } },
  )
  return res.data.data
}
