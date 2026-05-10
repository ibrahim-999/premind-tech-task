import type { User } from '@/features/auth/types'

export type PurchaseOrderStatus = 'draft' | 'submitted' | 'approved' | 'rejected' | 'cancelled'

export interface PurchaseOrderItem {
  id: number
  name: string
  quantity: number
  unit_price: number
  line_total: number
}

export interface PurchaseOrder {
  id: number
  title: string
  description: string | null
  category: string
  department_id: number | null
  amount: number
  status: PurchaseOrderStatus
  submission_count: number
  last_rejection_reason: string | null
  submitted_at: string | null
  approved_at: string | null
  rejected_at: string | null
  cancelled_at: string | null
  created_at: string | null
  updated_at: string | null
  requester?: User
  items?: PurchaseOrderItem[]
}

export interface PaginatedResponse<T> {
  data: T[]
  meta?: {
    current_page?: number
    last_page?: number
    per_page?: number
    total?: number
  }
  links?: unknown
}

export function isEditable(status: PurchaseOrderStatus): boolean {
  return status === 'draft' || status === 'rejected'
}

export function isCancellable(status: PurchaseOrderStatus): boolean {
  return status === 'draft' || status === 'submitted' || status === 'rejected'
}

export function canSubmit(status: PurchaseOrderStatus): boolean {
  return status === 'draft'
}

export function canResubmit(status: PurchaseOrderStatus): boolean {
  return status === 'rejected'
}
