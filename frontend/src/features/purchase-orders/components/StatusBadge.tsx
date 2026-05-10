import { Badge } from '@/shared/components/Badge'
import type { PurchaseOrderStatus } from '../types'

const labelFor: Record<PurchaseOrderStatus, string> = {
  draft: 'Draft',
  submitted: 'Submitted',
  approved: 'Approved',
  rejected: 'Rejected',
  cancelled: 'Cancelled',
}

const toneFor: Record<PurchaseOrderStatus, 'gray' | 'blue' | 'green' | 'red' | 'amber'> = {
  draft: 'gray',
  submitted: 'blue',
  approved: 'green',
  rejected: 'red',
  cancelled: 'gray',
}

export function StatusBadge({ status }: { status: PurchaseOrderStatus }) {
  return <Badge tone={toneFor[status]}>{labelFor[status]}</Badge>
}
